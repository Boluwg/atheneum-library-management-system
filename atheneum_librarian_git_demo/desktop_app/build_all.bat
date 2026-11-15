@echo off
setlocal
cd /d "%~dp0.."

py -m pip install -r desktop_app\requirements.txt
if errorlevel 1 exit /b %errorlevel%
py -m pip install pyinstaller
if errorlevel 1 exit /b %errorlevel%

pyinstaller --noconfirm --clean --onefile --windowed ^
  --name Atheneum ^
  --paths . ^
  desktop_app\launcher.py
if errorlevel 1 exit /b %errorlevel%

call desktop_app\build_installer.bat
