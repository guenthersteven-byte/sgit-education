@echo off
REM ============================================================================
REM sgiT Education - Docker Start Script
REM ============================================================================
REM 
REM Startet die Docker Container für die Education Platform
REM
REM @version 1.0
REM @date 05.12.2025
REM ============================================================================

echo.
echo  ============================================
echo   sgiT Education - Docker Starter
echo  ============================================
echo.

REM Ins Docker-Verzeichnis wechseln
cd /d "%~dp0"

REM Prüfen ob Docker läuft
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Docker Desktop laeuft nicht!
    echo  Bitte Docker Desktop starten und erneut versuchen.
    pause
    exit /b 1
)

echo  [OK] Docker Desktop laeuft
echo.

REM Container starten
echo  [INFO] Starte Container...
echo.
docker-compose up -d --build

if %errorlevel% neq 0 (
    echo.
    echo  [ERROR] Fehler beim Starten der Container!
    pause
    exit /b 1
)

echo.
echo  ============================================
echo   Container gestartet!
echo  ============================================
echo.
echo   Webseite:  http://localhost:8080
echo   Ollama:    http://localhost:11434
echo.
echo   Befehle:
echo   - Logs:    docker-compose logs -f
echo   - Stop:    docker-compose down
echo   - Shell:   docker-compose exec php bash
echo.
echo  ============================================
echo.

REM Browser öffnen
timeout /t 3 >nul
start http://localhost:8080

pause
