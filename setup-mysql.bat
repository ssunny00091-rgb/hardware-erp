@echo off
setlocal EnableExtensions
cd /d "%~dp0"

REM Pure Command Prompt MySQL import (PHP ke bina).
REM XAMPP MySQL START hona chahiye. Default: user root, password khali.

set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if not exist "%MYSQL%" set "MYSQL=D:\xampp\mysql\bin\mysql.exe"
if not exist "%MYSQL%" (
  echo mysql.exe nahi mili. Path check karo: C:\xampp\mysql\bin\mysql.exe
  pause
  exit /b 1
)

echo Database aur tables bana rahe hain...
"%MYSQL%" -h 127.0.0.1 -P 3306 -u root < "%~dp0sql\schema.sql"
if errorlevel 1 (
  echo Schema import fail. MySQL Start hai? Password laga hai to:
  echo   "%MYSQL%" -u root -p ^< sql\schema.sql
  pause
  exit /b 1
)

echo Sample data load...
"%MYSQL%" -h 127.0.0.1 -P 3306 -u root < "%~dp0sql\seed.sql"
if errorlevel 1 (
  echo Seed import fail.
  pause
  exit /b 1
)

if not exist "%~dp0.env" copy /Y "%~dp0.env.example" "%~dp0.env" >nul

echo.
echo Ho gaya. Browser: http://localhost/hardware-erp/index.php
pause
