@echo off
cd /d "%~dp0"
if not exist ".venv\Scripts\python.exe" (
  py -m venv .venv
  .venv\Scripts\python.exe -m pip install -r requirements.txt
)
set "PYTHONPATH=%~dp0.."
.venv\Scripts\python.exe launcher.py
