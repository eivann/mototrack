const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('Client :: ready');
  
  const script = `
set -e

cd /var/www/mototrack

# Ensure Reverb dependencies are installed just in case auto-deploy missed composer install
composer install --no-interaction --optimize-autoloader

# Update .env
sed -i '/^BROADCAST_CONNECTION=/d' .env
sed -i '/^REVERB_APP_ID=/d' .env
sed -i '/^REVERB_APP_KEY=/d' .env
sed -i '/^REVERB_APP_SECRET=/d' .env
sed -i '/^REVERB_HOST=/d' .env
sed -i '/^REVERB_PORT=/d' .env
sed -i '/^REVERB_SCHEME=/d' .env
sed -i '/^VITE_REVERB_/d' .env
sed -i '/^QUEUE_CONNECTION=/d' .env

echo "QUEUE_CONNECTION=database" >> .env
echo "BROADCAST_CONNECTION=reverb" >> .env
echo "REVERB_APP_ID=111222" >> .env
echo "REVERB_APP_KEY=mototrackkey123" >> .env
echo "REVERB_APP_SECRET=mototracksecret123" >> .env
echo "REVERB_HOST=mototrack.online" >> .env
echo "REVERB_PORT=443" >> .env
echo "REVERB_SCHEME=https" >> .env
echo 'VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"' >> .env
echo 'VITE_REVERB_HOST="\${REVERB_HOST}"' >> .env
echo 'VITE_REVERB_PORT="\${REVERB_PORT}"' >> .env
echo 'VITE_REVERB_SCHEME="\${REVERB_SCHEME}"' >> .env

# Configure Nginx for Reverb WebSockets
NGINX_CONF="/etc/nginx/sites-available/mototrack"
if ! grep -q "location /app" $NGINX_CONF; then
  sed -i '/error_page 404/i \\
    location /app {\\
        proxy_http_version 1.1;\\
        proxy_set_header Host $http_host;\\
        proxy_set_header Scheme $scheme;\\
        proxy_set_header SERVER_PORT $server_port;\\
        proxy_set_header REMOTE_ADDR $remote_addr;\\
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\\
        proxy_set_header Upgrade $http_upgrade;\\
        proxy_set_header Connection "Upgrade";\\
        proxy_pass http://0.0.0.0:8080;\\
    }' $NGINX_CONF
  
  nginx -t && systemctl reload nginx
fi

# Setup Supervisor
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

echo "Deployment finished!"
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
