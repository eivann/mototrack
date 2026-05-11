@echo off
echo ===================================================
echo    MotoTrack Sync - LOCAL to CLOUD
echo ===================================================
echo.
echo Step 1: Building assets (Vite)...
call npm run build

echo.
echo Step 2: Saving changes for GitHub...
git add .

echo.
echo Step 3: Committing changes...
set /p commit_msg="Enter a description of your change: "
git commit -m "%commit_msg%"

echo.
echo Step 4: Pushing to the Cloud...
git push origin main

echo.
echo ===================================================
echo    SUCCESS! Your site is being updated.
echo    Check Laravel Cloud for the progress.
echo ===================================================
echo.
pause