$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot\..
py -m pip install -r desktop_app\requirements.txt
py -m pip install pyinstaller
pyinstaller --noconfirm --clean --onefile --windowed `
  --name Atheneum `
  --paths . `
  desktop_app\launcher.py

Write-Host ""
Write-Host "Built dist\Atheneum.exe"

$innoCandidates = @(
  "$env:ProgramFiles(x86)\Inno Setup 6\ISCC.exe",
  "$env:ProgramFiles\Inno Setup 6\ISCC.exe"
)
$innoCompiler = $innoCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if ($innoCompiler) {
  & $innoCompiler desktop_app\Atheneum.iss
  Write-Host "Built dist\AtheneumSetup.exe"
} else {
  Write-Host "Inno Setup was not found; run desktop_app\build_installer.bat after installing Inno Setup 6."
}
