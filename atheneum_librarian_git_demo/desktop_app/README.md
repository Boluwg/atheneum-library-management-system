# Atheneum desktop circulation app

The packaged Atheneum launcher starts the local RFID bridge and displays the
existing PHP Atheneum website inside its own application window. The PHP
website remains the circulation UI. In the packaged app, the scanner connects
automatically and tapping a tag fills the RFID field without pressing a
connect or scan button.

## Building the one-file Windows app

For development/building only, Python 3.10+ is required:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\build_windows.ps1
```

The finished application is `dist\Atheneum.exe`. End users only need that
executable; they do not need Python, pyserial, or the bridge source files.

## Creating a normal Windows installer

Install the free Inno Setup 6 compiler on the build computer, then run:

```powershell
.\build_windows.ps1
```

If Inno Setup is installed, this also creates `dist\AtheneumSetup.exe`.
Alternatively, double-click `build_installer.bat` after
`dist\Atheneum.exe` has been built. The installer places Atheneum in Program
Files, creates Start Menu and desktop shortcuts, and includes an uninstaller.
End users only run `AtheneumSetup.exe`; they do not need PowerShell or Python.

For a one-click build without PowerShell, double-click `build_all.bat` after
installing Python and Inno Setup on the build computer.

## Running from source

Open PowerShell in this folder:

```powershell
py -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
$env:PYTHONPATH = ".."
.\.venv\Scripts\python.exe launcher.py
```

You can also double-click `run_windows.bat`.

The launcher automatically starts the local bridge and opens:

```text
https://your-server.example/library_system/login.php
```

The existing PHP pages handle login, borrower lookup, issue, return, and
styling. The PN532 sketch should continuously print one UID per line at 9600
baud. Credentials are entered only in the PHP login page and are not stored by
the launcher.

At login, choose the Arduino COM port once. That port is used for the rest of
the session. Click the scanner status badge in the upper-left corner to choose
a different port.

Admin pages show a scanner status badge in the upper-left corner. It changes to
“Scanner reconnecting...” if the Arduino is unplugged and updates automatically
when the bridge reconnects.

The app uses the Windows WebView2 runtime. Windows 10 and 11 normally already
include it; if it is missing, WebView2 must be installed once on that computer.
