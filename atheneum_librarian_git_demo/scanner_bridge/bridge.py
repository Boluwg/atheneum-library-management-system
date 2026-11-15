#!/usr/bin/env python3
"""Local serial-to-HTTP bridge for an Arduino PN532 RFID reader."""

from __future__ import annotations

import argparse
import json
import os
import queue
import re
import threading
import time
from http import HTTPStatus
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Any
from urllib.parse import parse_qs, urlparse

import serial
from serial.tools import list_ports


TAG_PREFIXES = ("UID:", "RFID:", "TAG:")
DEFAULT_BAUD_RATE = 9600
MAX_TAG_LENGTH = 100


def normalize_tag(value: str) -> str | None:
    tag = value.strip()
    if not tag:
        return None
    upper_tag = tag.upper()
    if upper_tag in {
        "NO_TAG",
        "NO TAG",
        "SCAN",
        "OK",
        "PN532_READY",
        "PN532_NOT_FOUND",
    }:
        return None
    for prefix in TAG_PREFIXES:
        if upper_tag.startswith(prefix):
            tag = tag[len(prefix) :].strip()
            break
    tag = re.sub(r"\s+", "", tag)
    if not tag or len(tag) > MAX_TAG_LENGTH:
        return None
    return tag


class SerialScanner:
    def __init__(self, baud_rate: int, protocol: str) -> None:
        self.baud_rate = baud_rate
        self.protocol = protocol
        self._serial: serial.Serial | None = None
        self._port_name: str | None = None
        self._queue: queue.Queue[str] = queue.Queue(maxsize=32)
        self._lock = threading.Lock()
        self._reader_thread: threading.Thread | None = None
        self._stop_event = threading.Event()

    @property
    def connected(self) -> bool:
        return self._serial is not None and self._serial.is_open

    @property
    def port_name(self) -> str | None:
        return self._port_name

    def connect(self, port_name: str | None = None) -> str:
        if self.connected:
            return self._port_name or ""
        selected_port = port_name or self._find_port()
        if not selected_port:
            raise RuntimeError("No serial port found. Connect the Arduino and try again.")

        connection = serial.Serial(
            selected_port,
            self.baud_rate,
            timeout=0.25,
            write_timeout=1,
        )
        with self._lock:
            self._serial = connection
            self._port_name = selected_port
            self._stop_event.clear()
        self._reader_thread = threading.Thread(target=self._read_loop, daemon=True)
        self._reader_thread.start()
        return selected_port

    def disconnect(self) -> None:
        self._stop_event.set()
        with self._lock:
            connection = self._serial
            self._serial = None
            self._port_name = None
        if connection is not None:
            connection.close()

    def request_tag(self, timeout: float) -> str:
        if not self.connected:
            raise RuntimeError("Scanner is not connected.")
        if self.protocol == "command":
            self._send_scan_command()
        try:
            return self._queue.get(timeout=max(0.1, min(timeout, 60.0)))
        except queue.Empty as error:
            raise TimeoutError("No RFID tag detected.") from error

    def clear_pending_tags(self) -> int:
        cleared = 0
        while True:
            try:
                self._queue.get_nowait()
            except queue.Empty:
                return cleared
            cleared += 1

    def status(self) -> dict[str, Any]:
        return {
            "connected": self.connected,
            "port": self.port_name,
            "baud_rate": self.baud_rate,
            "protocol": self.protocol,
            "queued_tags": self._queue.qsize(),
        }

    def _find_port(self) -> str | None:
        ports = list(list_ports.comports())
        if len(ports) == 1:
            return ports[0].device
        preferred = [
            item.device
            for item in ports
            if any(value in item.device.lower() for value in ("usb", "acm", "serial"))
        ]
        if len(preferred) == 1:
            return preferred[0]
        return None

    def _send_scan_command(self) -> None:
        with self._lock:
            connection = self._serial
        if connection is None or not connection.is_open:
            raise RuntimeError("Scanner is not connected.")
        connection.write(b"SCAN\n")
        connection.flush()

    def _read_loop(self) -> None:
        while not self._stop_event.is_set():
            with self._lock:
                connection = self._serial
            if connection is None:
                return
            try:
                raw_line = connection.readline()
            except serial.SerialException:
                self.disconnect()
                return
            if not raw_line:
                continue
            try:
                decoded = raw_line.decode("utf-8", errors="ignore")
            except UnicodeError:
                continue
            tag = normalize_tag(decoded)
            if tag is None:
                continue
            print(f"RFID tag detected: {tag}", flush=True)
            try:
                self._queue.put_nowait(tag)
            except queue.Full:
                try:
                    self._queue.get_nowait()
                except queue.Empty:
                    pass
                self._queue.put_nowait(tag)


class BridgeHandler(BaseHTTPRequestHandler):
    scanner: SerialScanner

    def do_OPTIONS(self) -> None:
        self.send_response(HTTPStatus.NO_CONTENT)
        self._write_cors_headers()
        self.end_headers()

    def do_GET(self) -> None:
        request = urlparse(self.path)
        if request.path == "/health":
            self._json_response(HTTPStatus.OK, self.scanner.status())
            return
        if request.path == "/ports":
            ports = [
                {
                    "device": item.device,
                    "description": item.description,
                    "manufacturer": item.manufacturer,
                }
                for item in list_ports.comports()
            ]
            self._json_response(HTTPStatus.OK, {"ports": ports})
            return
        if request.path == "/scan":
            timeout = float(parse_qs(request.query).get("timeout", ["15"])[0])
            try:
                tag = self.scanner.request_tag(timeout)
            except TimeoutError as error:
                self._json_response(HTTPStatus.REQUEST_TIMEOUT, {"error": str(error)})
            except RuntimeError as error:
                self._json_response(HTTPStatus.CONFLICT, {"error": str(error)})
            else:
                self._json_response(HTTPStatus.OK, {"tag": tag})
            return
        self._json_response(HTTPStatus.NOT_FOUND, {"error": "Not found."})

    def do_POST(self) -> None:
        request = urlparse(self.path)
        body = self._read_json()
        try:
            if request.path == "/connect":
                port = body.get("port") if isinstance(body, dict) else None
                selected = self.scanner.connect(port if isinstance(port, str) else None)
                self._json_response(HTTPStatus.OK, {"connected": True, "port": selected})
                return
            if request.path == "/reset":
                cleared = self.scanner.clear_pending_tags()
                self._json_response(HTTPStatus.OK, {"cleared": cleared})
                return
            if request.path == "/disconnect":
                self.scanner.disconnect()
                self._json_response(HTTPStatus.OK, {"connected": False})
                return
        except (OSError, serial.SerialException, RuntimeError) as error:
            self._json_response(HTTPStatus.BAD_REQUEST, {"error": str(error)})
            return
        self._json_response(HTTPStatus.NOT_FOUND, {"error": "Not found."})

    def log_message(self, format: str, *args: Any) -> None:
        return

    def _read_json(self) -> Any:
        length = int(self.headers.get("Content-Length", "0"))
        if length == 0:
            return {}
        return json.loads(self.rfile.read(length))

    def _json_response(self, status: HTTPStatus, payload: dict[str, Any]) -> None:
        encoded = json.dumps(payload).encode("utf-8")
        self.send_response(status)
        self._write_cors_headers()
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(encoded)))
        self.end_headers()
        self.wfile.write(encoded)

    def _write_cors_headers(self) -> None:
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.send_header("Access-Control-Allow-Private-Network", "true")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Atheneum Arduino RFID bridge")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=8765)
    parser.add_argument("--baud", type=int, default=DEFAULT_BAUD_RATE)
    parser.add_argument(
        "--protocol",
        choices=("continuous", "command"),
        default="continuous",
        help="continuous reads UID lines; command sends SCAN\\n before each scan",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    scanner = SerialScanner(args.baud, args.protocol)
    handler = type("ConfiguredBridgeHandler", (BridgeHandler,), {"scanner": scanner})
    server = ThreadingHTTPServer((args.host, args.port), handler)
    print(
        f"Atheneum scanner bridge listening on http://{args.host}:{args.port} "
        f"(protocol={args.protocol}, baud={args.baud})"
    )
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        scanner.disconnect()
        server.server_close()


if __name__ == "__main__":
    main()
