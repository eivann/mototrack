const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('Client :: ready');
  
  const script = `
set -e

cd /var/www/mototrack

echo "Pulling latest code..."
if [ ! -d ".git" ]; then
    echo "Initializing git repo..."
    git init
    git remote add origin https://github.com/eivann/mototrack.git
fi
git config --global --add safe.directory /var/www/mototrack
git remote add origin https://github.com/eivann/mototrack.git || true
git fetch origin main
git reset --hard origin/main

echo "Updating SMTP config in .env..."
sed -i '/^MAIL_/d' .env
echo "MAIL_MAILER=smtp" >> .env
echo "MAIL_HOST=smtp.queensmtp.com" >> .env
echo "MAIL_PORT=465" >> .env
echo "MAIL_USERNAME=yawyaw-2f7zbw@smtp.queensmtp.com" >> .env
echo "MAIL_PASSWORD=5sDfp4WMFHBTA3Hd4QVH5sDfp4WMFHBTA3Hd4QVH" >> .env
echo "MAIL_ENCRYPTION=ssl" >> .env
echo 'MAIL_FROM_ADDRESS="hello@mototrack.online"' >> .env
echo 'MAIL_FROM_NAME="\${APP_NAME}"' >> .env

echo "Setting up Cron job for Laravel Scheduler..."
if ! crontab -l | grep -q 'schedule:run'; then
    (crontab -l 2>/dev/null; echo "* * * * * cd /var/www/mototrack && php artisan schedule:run >> /dev/null 2>&1") | crontab -
fi

echo "Clearing caches and restarting workers..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Restart supervisor for reverb and workers if running
systemctl restart supervisor || true

echo "Done!"
`;

  conn.exec(script, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('close', (code, signal) => {
      console.log('Stream :: close :: code: ' + code + ', signal: ' + signal);
      console.log('Output:\n' + out);
      conn.end();
    }).on('data', (data) => {
      out += data;
    }).stderr.on('data', (data) => {
      console.log('STDERR: ' + data);
    });
  });
}).connect({
  host: '147.93.172.239',
  port: 22,
  username: 'root',
  password: 'response'
});
