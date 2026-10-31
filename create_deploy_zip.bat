@echo off
setlocal
echo ===================================================
echo   PocketPilot Deployment Zip Packager
echo ===================================================
echo Packaging clean production zip for upload...
echo.

set "TARGET_DIR=%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -Command "$dir = '%TARGET_DIR%'.TrimEnd('\'); $dest = Join-Path $dir 'pocketpilot-deploy.zip'; if (Test-Path $dest) { Remove-Item $dest -Force }; $items = Get-ChildItem -Path $dir | Where-Object { $_.Name -notin @('.git', '.github', '.env', 'pocketpilot-deploy.zip', 'PocketPilot.bat', 'start_pocketpilot.bat', 'create_deploy_zip.bat') }; Compress-Archive -Path $items.FullName -DestinationPath $dest -Force; Write-Host 'Success! Created pocketpilot-deploy.zip in workspace folder.' -ForegroundColor Green;"

echo.
echo Ready! You can upload 'pocketpilot-deploy.zip' directly to:
echo  - cPanel File Manager (Extract inside public_html)
echo  - InfinityFree / Hostinger / 000webhost / Apache VPS
echo.
pause
