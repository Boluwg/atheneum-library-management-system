#!/usr/bin/env python3
"""Desktop Atheneum window with an embedded PHP site and RFID bridge."""

from __future__ import annotations

import threading
from http.server import ThreadingHTTPServer
from pathlib import Path

import webview

from scanner_bridge.bridge import BridgeHandler, SerialScanner


SERVER_URL = "https://your-server.example/library_system/login.php?atheneum_desktop=1"
BRIDGE_HOST = "127.0.0.1"
BRIDGE_PORT = 8765


class DesktopApi:
    def save_csv(self, csv_text: str) -> dict[str, object]:
        if not isinstance(csv_text, str):
            return {"saved": False, "error": "Invalid CSV data."}
        try:
            selected = webview.windows[0].create_file_dialog(
                webview.SAVE_DIALOG,
                save_filename="library_books.csv",
                file_types=("CSV files (*.csv)", "All files (*.*)"),
            )
            if not selected:
                return {"saved": False, "cancelled": True}
            target = selected[0] if isinstance(selected, (list, tuple)) else selected
            with Path(target).open("w", encoding="utf-8-sig", newline="") as output:
                output.write(csv_text)
            return {"saved": True, "path": str(target)}
        except OSError as error:
            return {"saved": False, "error": str(error)}


def main() -> None:
    scanner = SerialScanner(9600, "continuous")
    handler = type(
        "LauncherBridgeHandler",
        (BridgeHandler,),
        {"scanner": scanner},
    )
    server = ThreadingHTTPServer((BRIDGE_HOST, BRIDGE_PORT), handler)
    server_thread = threading.Thread(target=server.serve_forever, daemon=True)
    server_thread.start()

    webview.create_window(
        "Atheneum",
        SERVER_URL,
        width=1365,
        height=850,
        min_size=(960, 640),
        text_select=True,
        js_api=DesktopApi(),
    )
    try:
        webview.start(
            gui="edgechromium",
            debug=False,
            user_agent="AtheneumDesktop/1.0",
        )
    finally:
        scanner.disconnect()
        server.shutdown()
        server.server_close()


if __name__ == "__main__":
    main()
