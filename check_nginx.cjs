const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  const script = `
ls -la /etc/nginx/sites-enabled/
cat /etc/nginx/sites-enabled/*
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
