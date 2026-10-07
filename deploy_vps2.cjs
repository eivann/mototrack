const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('Client :: ready');
  
  const script = `
cd /var/www/mototrack
git pull origin main

composer install --no-interaction --optimize-autoloader

# Update .env
if ! grep -q "REVERB_APP_ID" .env; then
  echo "BROADCAST_CONNECTION=reverb" >> .env
  echo "REVERB_APP_ID=828384" >> .env
  echo "REVERB_APP_KEY=mototrackkey" >> .env
  echo "REVERB_APP_SECRET=mototracksecret" >> .env
  echo "REVERB_HOST=mototrack.online" >> .env
  echo "REVERB_PORT=8080" >> .env
  echo "REVERB_SCHEME=http" >> .env
  echo 'VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"' >> .env
  echo 'VITE_REVERB_HOST="${REVERB_HOST}"' >> .env
  echo 'VITE_REVERB_PORT="${REVERB_PORT}"' >> .env
  echo 'VITE_REVERB_SCHEME="${REVERB_SCHEME}"' >> .env
else
  echo "Reverb already configured in .env"
fi

apt-get update
apt-get install -y supervisor

cat > /etc/supervisor/conf.d/mototrack-reverb.conf << 'EOF'
[program:mototrack-reverb]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mototrack/artisan reverb:start
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/log/mototrack-reverb.log
EOF

cat > /etc/supervisor/conf.d/mototrack-queue.conf << 'EOF'
[program:mototrack-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mototrack/artisan queue:work
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/log/mototrack-queue.log
EOF

supervisorctl reread
supervisorctl update
supervisorctl restart all

php artisan optimize:clear
php artisan view:clear
`;

  conn.exec(script, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('close', (code, signal) => {
      console.log('OUTPUT:', out);
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
