@echo off
echo =====================================
echo  MotoTrack Quick Git Push Script
echo =====================================

:: Check if git is installed
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Git is not installed or not in your system PATH.
    pause
    exit /b
)

:: Show current status
echo.
echo Current Git Status:
git status -s
echo.

:: Prompt for commit message
set /p commit_msg="Enter your commit message: "

:: If message is empty, use a default one
if "%commit_msg%"=="" (
    set commit_msg="Auto-commit: Updates to project"
    echo No message provided. Using default: "Auto-commit: Updates to project"
)

:: Stage, Commit, and Push
echo.
echo [1/3] Staging files...
git add .

echo [2/3] Committing changes...
git commit -m "%commit_msg%"

echo [3/3] Pushing to GitHub...
git push

echo.
echo =====================================
echo  Done! Your changes have been pushed.
echo =====================================
pause