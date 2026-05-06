@echo off
echo ===================================================
echo    MotoTrack Local Deployment Script
echo ===================================================
echo.
echo Step 1: Compiling latest CSS and JavaScript...
call npm run build

echo.
echo Step 2: Preparing files for GitHub...
git add .
git add -f public/build

echo.
echo Step 3: Committing changes...
set "timestamp=%date% %time%"
git commit -m "Auto-deployment update: %timestamp%"

echo.
echo Step 4: Pushing to GitHub...
git push

echo.
echo ===================================================
echo    Done! Your code is now safely on GitHub.
echo ===================================================
echo.
echo Next step: Open your cPanel Terminal and run:
echo    cd ~/mototrack_app ^&^& git pull ^&^& php artisan migrate --force ^&^& php artisan optimize:clear
echo.
pause
