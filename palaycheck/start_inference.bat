@echo off
setlocal
cd /d "%~dp0"

if not exist "venv\Scripts\python.exe" (
    echo Python virtual environment not found at venv\Scripts\python.exe
    echo Create it and install requirements.txt first.
    exit /b 1
)

if not exist "models\palaycheck_best.pt" (
    echo Detection model not found at models\palaycheck_best.pt
    exit /b 1
)

echo Starting local pest detection service on http://127.0.0.1:5001
"venv\Scripts\python.exe" "inference_service.py"
