@echo off
REM ============================================================================
REM sgiT Education - Docker Stop Script
REM ============================================================================

echo.
echo  ============================================
echo   sgiT Education - Docker Stopper
echo  ============================================
echo.

cd /d "%~dp0"

echo  [INFO] Stoppe Container...
docker-compose down

echo.
echo  [OK] Container gestoppt!
echo.

pause
