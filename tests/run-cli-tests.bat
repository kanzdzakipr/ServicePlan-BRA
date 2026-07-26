@echo off
set NODE_EXE="C:\laragon\bin\nodejs\node-v22\node.exe"

where node >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    set NODE_EXE=node
)

%NODE_EXE% "%~dp0run-cli-tests.js"
