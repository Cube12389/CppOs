@echo off
REM ============================================================
REM  fix_ai_stream.bat
REM  Double-click to run. It self-elevates and fixes the AI
REM  streaming buffering issue (mod_fcgid FcgidOutputBufferSize).
REM ============================================================

REM Ask for administrator rights if not already elevated
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Requesting administrator privileges...
    powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

REM Already elevated: run the fix script
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0fix_ai_stream.ps1"
