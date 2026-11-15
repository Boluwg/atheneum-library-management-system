# Atheneum scanner bridge

This local bridge runs on the computer physically connected to the Arduino Uno
and PN532. The PHP application and database can run on a separate server.

## Setup

Install Python 3.10 or newer and the dependency:

```bash
python -m pip install -r requirements.txt
```

Start the bridge:

```bash
python bridge.py
```

The default mode expects the Arduino to continuously print one RFID UID per line
at 9600 baud. If the sketch waits for a command, use:

```bash
python bridge.py --protocol command
```

The admin circulation pages connect to `http://127.0.0.1:8765`. The bridge
automatically selects the only connected serial port; when more than one exists,
the `/ports` endpoint can be used to select a port explicitly.

The Arduino sketch should print values such as `04A1B2C3` or `UID: 04 A1 B2 C3`.
The bridge ignores empty lines, `NO_TAG`, and serial status messages.
