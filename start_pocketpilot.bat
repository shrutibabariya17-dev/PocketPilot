@echo off
title PocketPilot - Student Financial Co-Pilot
cd /d "%~dp0"

echo ======================================================================
echo                POCKETPILOT - STUDENT FINANCIAL CO-PILOT
echo ======================================================================
echo.

:: 1. Check if MySQL is running, start via XAMPP if needed
tasklist /fi "imagename eq mysqld.exe" | find /i "mysqld.exe" >nul
if errorlevel 1 (
    echo [1/3] Starting MySQL database from XAMPP...
    if exist "C:\xampp\mysql_start.bat" (
        start "" /min "C:\xampp\mysql_start.bat"
    ) else if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" /min "C:\xampp\mysql\bin\mysqld.exe"
    )
    timeout /t 2 /nobreak >nul
) else (
    echo [1/3] MySQL database is already active and running.
)

:: 2. Auto-launch the website in the default browser after 2 seconds
echo [2/3] Launching PocketPilot in your default web browser...
start "" cmd /c "timeout /t 2 /nobreak >nul & start http://localhost:8080/index.php"

:: 3. Start the PHP server on port 8080
echo [3/3] Starting PocketPilot Web Server on http://localhost:8080 ...
echo.
echo ======================================================================
echo   PocketPilot is running live at: http://localhost:8080
echo.
echo   Keep this window open while using the website!
echo   To shut down the server, simply close this window or press Ctrl+C.
echo ======================================================================
echo.

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8080 -t "%~dp0"
) else (
    php -S localhost:8080 -t "%~dp0"
)

pause

