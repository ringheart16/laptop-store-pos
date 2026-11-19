@echo off
title Laptop Store Management System
cd /d "%~dp0"
echo ==================================================
echo   Starting Laptop Store Management System...
echo ==================================================
echo.
node server.js
if %errorlevel% neq 0 (
    echo.
    echo Node.js failed, falling back to direct PHP server...
    php -S 0.0.0.0:8000
)
pause
