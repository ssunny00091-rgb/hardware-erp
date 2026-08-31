@echo off
setlocal EnableExtensions
cd /d "%~dp0"

echo ========================================
echo  Hardware ERP - Command Prompt setup
echo ========================================
echo.
echo Pehle XAMPP Control Panel se Apache + MySQL START karo.
echo.

set "PHP_EXE="
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
if exist "%~dp0..\php\php.exe" set "PHP_EXE=%~dp0..\php\php.exe"
where php >nul 2>nul && if not defined PHP_EXE for /f "delims=" %%I in ('where php') do set "PHP_EXE=%%I"

if not defined PHP_EXE (
  echo ERROR: php.exe nahi mili.
  echo XAMPP install karke ye file se chalao:
  echo   C:\xampp\php\php.exe setup.php
  echo.
  pause
  exit /b 1
)

echo PHP: %PHP_EXE%
echo.

REM /yes = bina poochhe XAMPP default (root, password khali)
if /I "%~1"=="/yes" (
  "%PHP_EXE%" "%~dp0setup.php" --yes --host=127.0.0.1 --port=3306 --name=hardware_erp --user=root --pass=
) else if /I "%~1"=="--yes" (
  "%PHP_EXE%" "%~dp0setup.php" --yes --host=127.0.0.1 --port=3306 --name=hardware_erp --user=root --pass=
) else (
  "%PHP_EXE%" "%~dp0setup.php" %*
)

set "ERR=%ERRORLEVEL%"
echo.
if not "%ERR%"=="0" (
  echo Setup fail ho gaya. Upar wala ERROR padho.
) else (
  echo Browser: http://localhost/hardware-erp/index.php
)
echo.
pause
exit /b %ERR%
