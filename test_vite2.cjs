const fs = require('fs');
const file = fs.readdirSync('public/build/assets').find(f => f.startsWith('app-') && f.endsWith('.js'));
const content = fs.readFileSync('public/build/assets/' + file, 'utf8');
console.log("Reverb Host:", content.match(/wsHost:.*?\,/g));
console.log("Reverb Port:", content.match(/wsPort:.*?\,/g));
console.log("Force TLS:", content.match(/forceTLS:.*?\,/g));
