@echo off
REM 3Cmanage/setup.bat

echo ========================================
echo 3C Management System - Database Setup
echo ========================================
echo.

REM Check if mysql.exe is available (assuming XAMPP installation)
set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe
if not exist "%MYSQL_PATH%" (
    set MYSQL_PATH=mysql
)

REM Prompt for MySQL root password
set /p MYSQL_PWD="Enter MySQL root password (leave blank if none): "

echo Testing MySQL connection...
"%MYSQL_PATH%" -u root --password="%MYSQL_PWD%" -e "SELECT 1" > nul 2>&1

if %ERRORLEVEL% neq 0 (
    echo Error: Could not connect to MySQL server. Check your password and try again.
    exit /b 1
)

echo MySQL connection successful!
echo.

REM Create database and apply schema
echo Creating database and applying schema...
"%MYSQL_PATH%" -u root --password="%MYSQL_PWD%" < database\init.sql

if %ERRORLEVEL% neq 0 (
    echo Error: Failed to initialize database.
    exit /b 1
)

echo Database initialized successfully!
echo.
echo Sample admin credentials:
echo   Username: admin
echo   Password: password
echo.
echo Make sure to update the .env file with correct database credentials.

REM Check for .env file
if not exist ".env" (
    echo Creating .env file from template...
    copy .env.example .env
    echo Please edit .env with appropriate settings.
) else (
    echo .env file already exists.
)

echo.
echo Setup completed!
echo ========================================
