# Atheneum Library Management System

Atheneum is a PHP/MySQL librarian portal with RFID-assisted circulation,
inventory management, book categories, reservations, reports, CSV import/export,
admin account settings, and a packaged Windows scanner application.

## Demo credentials

```text
Username: admin
Password: password
```

These credentials are intentionally included only for local demonstration.
Change them immediately if this project is deployed anywhere public.

## Local web installation

### Requirements

- PHP 8.1 or newer with `mysqli`
- MariaDB or MySQL
- Apache, Nginx, or PHP's built-in development server

### Database

Create the demo database and tables:

```bash
mysql -u root -p < database/atheneum_demo.sql
```

Configure these environment variables for PHP. `.env.example` is a reference
file; this project does not load `.env` automatically:

```text
LIBRARY_DB_HOST=127.0.0.1
LIBRARY_DB_USER=library_user
LIBRARY_DB_PASSWORD=your-local-database-password
LIBRARY_DB_NAME=atheneum_demo
LIBRARY_DB_PORT=3306
```

For a quick local test, serve the project directory:

```bash
php -S 127.0.0.1:8080
```

Open `http://127.0.0.1:8080/login.php`, then use the demo credentials above.
The SQL file includes a sample student and book.

## Windows application installation

The Windows application connects the PHP librarian portal to an Arduino PN532
reader. The end user does not need Python, PowerShell, or the bridge source.

### Build the installer

The build computer needs:

- Python 3.10 or newer
- Internet access while dependencies are installed
- Inno Setup 6

Set the server URL in `desktop_app/launcher.py` to the HTTPS URL where this PHP
application is deployed, then double-click:

```text
desktop_app\build_all.bat
```

The installer is created at:

```text
dist\AtheneumSetup.exe
```

### Install and use it

1. Run `AtheneumSetup.exe`.
2. Accept the Program Files installation location.
3. Use the Start Menu or Desktop shortcut.
4. At login, select the Arduino COM port.
5. Connect the PN532 reader and keep the Arduino Serial Monitor closed.
6. Log in and use the RFID issue/return workflow.

Windows 10 and 11 normally include the WebView2 runtime. If it is missing,
install that Microsoft runtime once before launching Atheneum.

The Arduino PN532 sketch should output one RFID value per line at 9600 baud.

## Arduino sketch

The ready-to-upload sketch is in
`arduino/atheneum_pn532/atheneum_pn532.ino`.

Install the **Adafruit PN532** library from the Arduino Library Manager, then
open the sketch in the Arduino IDE and upload it to an Arduino Uno. The sketch
assumes I2C wiring:

- PN532 IRQ to Arduino D2
- PN532 RESET to Arduino D3
- PN532 SDA/SCL to the Arduino I2C pins

It prints one uppercase hexadecimal UID per line at 9600 baud and ignores
reader status messages in the bridge.

## Main features

- Admin dashboard and account settings
- Book, student, teacher, and reservation management
- RFID issue and return workflow
- Automatic scanner reconnect and COM-port selection
- CSV book import and export
- Dark/light theme
- Password recovery request workflow
- Windows executable and installer configuration

## Security note

This repository is a sanitized demonstration copy. Never commit production
database dumps, credentials, private certificates, uploaded files, or real
student/library data. Replace the demo password before using the project
outside a local demonstration.
