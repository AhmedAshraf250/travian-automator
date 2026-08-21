@echo off
setlocal

cd /d "%~dp0"

echo Starting Travian Multi-Account Automation...
echo.
echo This window is the application runtime.
echo Keep it open while the automation should run.
echo Close it to stop the web server, queue worker, and scheduler.
echo.

php artisan travian:runtime

echo.
echo Travian runtime stopped.
pause
