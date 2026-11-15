#!/usr/bin/env python3
"""Standalone Atheneum circulation app for an Arduino PN532 reader."""

from __future__ import annotations

import json
import queue
import threading
import tkinter as tk
from http.cookiejar import CookieJar
from tkinter import messagebox, ttk
from urllib import error, request

import serial
from serial.tools import list_ports


DEFAULT_SERVER = "https://your-server.example/library_system"
BAUD_RATE = 9600


class RemoteAtheneum:
    def __init__(self, base_url: str, username: str, password: str) -> None:
        self.base_url = base_url.rstrip("/")
        self.username = username
        self.password = password
        self.cookie_jar = CookieJar()
        self.opener = request.build_opener(request.HTTPCookieProcessor(self.cookie_jar))
        self.csrf_token = ""

    def _request(
        self,
        path: str,
        method: str = "GET",
        payload: dict[str, str] | None = None,
    ) -> dict:
        data = None
        headers = {"Accept": "application/json"}
        if payload is not None:
            data = json.dumps(payload).encode("utf-8")
            headers["Content-Type"] = "application/json"
            headers["X-CSRF-Token"] = self.csrf_token
        http_request = request.Request(
            f"{self.base_url}{path}", data=data, headers=headers, method=method
        )
        try:
            with self.opener.open(http_request, timeout=15) as response:
                return json.loads(response.read().decode("utf-8"))
        except error.HTTPError as exc:
            try:
                body = json.loads(exc.read().decode("utf-8"))
            except (ValueError, UnicodeDecodeError):
                body = {}
            raise RuntimeError(body.get("error", f"Server returned HTTP {exc.code}.")) from exc
        except error.URLError as exc:
            raise RuntimeError(f"Cannot reach the Atheneum server: {exc.reason}") from exc

    def login(self) -> None:
        response = self._request(
            "/api/login.php",
            "POST",
            {"username": self.username, "password": self.password},
        )
        self.csrf_token = response["csrf_token"]

    def circulate(self, action: str, rfid: str, user_id: str = "") -> dict:
        payload = {"action": action, "rfid": rfid}
        if user_id:
            payload["user_id"] = user_id
        return self._request("/api/circulation.php", "POST", payload)


class SerialReader:
    def __init__(self, on_tag) -> None:
        self.on_tag = on_tag
        self.connection: serial.Serial | None = None
        self.stop_event = threading.Event()

    def ports(self) -> list[str]:
        return [port.device for port in list_ports.comports()]

    def connect(self, port_name: str) -> None:
        self.connection = serial.Serial(port_name, BAUD_RATE, timeout=0.25)
        self.stop_event.clear()
        threading.Thread(target=self._read_loop, daemon=True).start()

    def disconnect(self) -> None:
        self.stop_event.set()
        if self.connection is not None:
            self.connection.close()
            self.connection = None

    def _read_loop(self) -> None:
        while not self.stop_event.is_set() and self.connection is not None:
            raw_line = self.connection.readline()
            if not raw_line:
                continue
            text = raw_line.decode("utf-8", errors="ignore").strip()
            if text.upper().startswith("UID:"):
                text = text[4:].strip()
            tag = "".join(text.split())
            if tag and tag.upper() not in {"NO_TAG", "PN532_READY", "SCAN"}:
                self.on_tag(tag)


class AtheneumApp(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.title("Atheneum Circulation")
        self.geometry("760x650")
        self.minsize(680, 580)
        self.configure(bg="#0f1117")
        self._configure_styles()
        self.reader = SerialReader(self._queue_tag)
        self.tag_queue: queue.Queue[str] = queue.Queue()
        self.remote: RemoteAtheneum | None = None
        self._build_ui()
        self.after(100, self._drain_tags)
        self.protocol("WM_DELETE_WINDOW", self._close)

    def _configure_styles(self) -> None:
        style = ttk.Style(self)
        style.theme_use("clam")
        style.configure("App.TFrame", background="#0f1117")
        style.configure(
            "Card.TLabelframe",
            background="#181c27",
            foreground="#c9a84c",
            bordercolor="#30374a",
            lightcolor="#30374a",
            darkcolor="#30374a",
            relief="solid",
            borderwidth=1,
        )
        style.configure(
            "Card.TLabelframe.Label",
            background="#181c27",
            foreground="#c9a84c",
            font=("Segoe UI", 10, "bold"),
        )
        style.configure(
            "App.TLabel",
            background="#181c27",
            foreground="#e8eaf0",
            font=("Segoe UI", 10),
        )
        style.configure(
            "Muted.TLabel",
            background="#0f1117",
            foreground="#7a8298",
            font=("Segoe UI", 10),
        )
        style.configure(
            "Title.TLabel",
            background="#0f1117",
            foreground="#c9a84c",
            font=("Segoe UI", 24, "bold"),
        )
        style.configure(
            "Subtitle.TLabel",
            background="#0f1117",
            foreground="#7a8298",
            font=("Segoe UI", 10),
        )
        style.configure(
            "Field.TEntry",
            fieldbackground="#202536",
            foreground="#f2f3f7",
            insertcolor="#f2f3f7",
            bordercolor="#3b4358",
            lightcolor="#3b4358",
            darkcolor="#3b4358",
            padding=8,
        )
        style.configure(
            "Field.TCombobox",
            fieldbackground="#202536",
            foreground="#f2f3f7",
            bordercolor="#3b4358",
            lightcolor="#3b4358",
            darkcolor="#3b4358",
            padding=6,
        )
        style.configure(
            "Primary.TButton",
            background="#c9a84c",
            foreground="#171a22",
            bordercolor="#c9a84c",
            padding=(14, 8),
            font=("Segoe UI", 10, "bold"),
        )
        style.map(
            "Primary.TButton",
            background=[("active", "#e0c36d"), ("pressed", "#a98b37")],
        )
        style.configure(
            "Secondary.TButton",
            background="#252b3b",
            foreground="#e8eaf0",
            bordercolor="#3b4358",
            padding=(12, 7),
            font=("Segoe UI", 10),
        )
        style.map(
            "Secondary.TButton",
            background=[("active", "#303950"), ("pressed", "#1e2330")],
        )
        style.configure(
            "Status.TLabel",
            background="#202536",
            foreground="#aeb6c9",
            padding=12,
            font=("Segoe UI", 10),
        )

    def _build_ui(self) -> None:
        frame = ttk.Frame(self, padding=28, style="App.TFrame")
        frame.pack(fill="both", expand=True)
        ttk.Label(frame, text="Atheneum", style="Title.TLabel").pack(anchor="w")
        ttk.Label(
            frame,
            text="Library circulation desk  ·  RFID operations",
            style="Subtitle.TLabel",
        ).pack(anchor="w", pady=(2, 18))

        server = ttk.LabelFrame(
            frame, text="  Server connection  ", padding=16, style="Card.TLabelframe"
        )
        server.pack(fill="x", pady=(16, 8))
        self.server_entry = self._field(server, "Server URL", DEFAULT_SERVER, 0)
        self.username_entry = self._field(server, "Admin username", "", 1)
        self.password_entry = self._field(server, "Admin password", "", 2, True)
        ttk.Button(
            server,
            text="Connect to server",
            style="Primary.TButton",
            command=self._connect_server,
        ).grid(
            row=3, column=1, sticky="e", pady=(12, 0)
        )

        scanner = ttk.LabelFrame(
            frame, text="  Arduino PN532 scanner  ", padding=16, style="Card.TLabelframe"
        )
        scanner.pack(fill="x", pady=8)
        ttk.Label(scanner, text="Serial port", style="App.TLabel").grid(
            row=0, column=0, sticky="w"
        )
        self.port_combo = ttk.Combobox(
            scanner, values=self.reader.ports(), width=18, style="Field.TCombobox"
        )
        self.port_combo.grid(row=0, column=1, sticky="w", padx=8)
        ttk.Button(
            scanner, text="Refresh ports", style="Secondary.TButton", command=self._refresh_ports
        ).grid(
            row=0, column=2, padx=(4, 0)
        )
        ttk.Button(
            scanner,
            text="Connect scanner",
            style="Secondary.TButton",
            command=self._connect_scanner,
        ).grid(
            row=1, column=1, sticky="w", padx=8, pady=(12, 0)
        )

        circulation = ttk.LabelFrame(
            frame, text="  Book circulation  ", padding=16, style="Card.TLabelframe"
        )
        circulation.pack(fill="x", pady=8)
        self.user_entry = self._field(circulation, "Borrower ID", "", 0)
        self.rfid_entry = self._field(circulation, "RFID tag", "", 1)
        ttk.Button(
            circulation,
            text="Issue book",
            style="Primary.TButton",
            command=lambda: self._run_action("issue"),
        ).grid(
            row=2, column=1, sticky="w", padx=8, pady=(12, 0)
        )
        ttk.Button(
            circulation,
            text="Return book",
            style="Secondary.TButton",
            command=lambda: self._run_action("return"),
        ).grid(
            row=2, column=2, sticky="w", pady=(12, 0)
        )

        self.status = ttk.Label(
            frame,
            text="Connect the server and scanner to begin.",
            style="Status.TLabel",
        )
        self.status.pack(fill="x", anchor="w", pady=(16, 0))

    def _field(
        self, parent: ttk.Widget, label: str, value: str, row: int, password: bool = False
    ) -> ttk.Entry:
        ttk.Label(parent, text=label, style="App.TLabel").grid(
            row=row, column=0, sticky="w", pady=5
        )
        entry = ttk.Entry(
            parent,
            width=48,
            show="*" if password else "",
            style="Field.TEntry",
        )
        entry.insert(0, value)
        entry.grid(row=row, column=1, columnspan=2, sticky="ew", padx=8, pady=3)
        parent.columnconfigure(1, weight=1)
        return entry

    def _refresh_ports(self) -> None:
        self.port_combo["values"] = self.reader.ports()
        if self.port_combo["values"] and not self.port_combo.get():
            self.port_combo.set(self.port_combo["values"][0])

    def _connect_server(self) -> None:
        self.status.config(text="Connecting to server...")
        self._background(self._login_remote, "Server connected.")

    def _login_remote(self) -> None:
        remote = RemoteAtheneum(
            self.server_entry.get(),
            self.username_entry.get(),
            self.password_entry.get(),
        )
        remote.login()
        self.remote = remote

    def _connect_scanner(self) -> None:
        try:
            self.reader.connect(self.port_combo.get())
            self.status.config(text=f"Scanner connected on {self.port_combo.get()}.")
        except Exception as exc:
            messagebox.showerror("Scanner connection failed", str(exc))

    def _queue_tag(self, tag: str) -> None:
        self.tag_queue.put(tag)

    def _drain_tags(self) -> None:
        try:
            while True:
                tag = self.tag_queue.get_nowait()
                self.rfid_entry.delete(0, tk.END)
                self.rfid_entry.insert(0, tag)
                self.status.config(text=f"RFID scanned: {tag}")
        except queue.Empty:
            pass
        self.after(100, self._drain_tags)

    def _run_action(self, action: str) -> None:
        if self.remote is None:
            messagebox.showwarning("Server not connected", "Connect to the server first.")
            return
        rfid = self.rfid_entry.get().strip()
        user_id = self.user_entry.get().strip()
        if not rfid or (action == "issue" and not user_id):
            messagebox.showwarning("Missing information", "Enter the required fields.")
            return
        self.status.config(text=f"Submitting {action}...")
        self._background(lambda: self.remote.circulate(action, rfid, user_id), "Completed.")

    def _background(self, callback, success_message: str) -> None:
        def run() -> None:
            try:
                callback()
            except Exception as exc:
                self.after(
                    0,
                    lambda exc=exc: self.status.config(text=f"Error: {exc}"),
                )
                self.after(
                    0,
                    lambda exc=exc: messagebox.showerror("Atheneum error", str(exc)),
                )
            else:
                self.after(0, lambda: self.status.config(text=success_message))

        threading.Thread(target=run, daemon=True).start()

    def _close(self) -> None:
        self.reader.disconnect()
        self.destroy()


if __name__ == "__main__":
    AtheneumApp().mainloop()
