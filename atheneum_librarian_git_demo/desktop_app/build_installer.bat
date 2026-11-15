@echo off
setlocal
cd /d "%~dp0.."

if not exist "dist\Atheneum.exe" (
  echo dist\Atheneum.exe was not found.
  echo Build the application first with build_windows.ps1.
  pause
  exit /b 1
)

set "ISCC="
if exist "%ProgramFiles(x86)%\Inno Setup 6\ISCC.exe" set "ISCC=%ProgramFiles(x86)%\Inno Setup 6\ISCC.exe"
if not defined ISCC if exist "%ProgramFiles%\Inno Setup 6\ISCC.exe" set "ISCC=%ProgramFiles%\Inno Setup 6\ISCC.exe"

if not defined ISCC (
  echo Inno Setup 6 was not found.
  echo Install the free Inno Setup compiler, then run this file again.
  pause
  exit /b 1
)

"%ISCC%" "desktop_app\Atheneum.iss"
if errorlevel 1 exit /b %errorlevel%

echo.
echo Created dist\AtheneumSetup.exe
pause
