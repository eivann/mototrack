const fs = require('fs');
const file = fs.readdirSync('public/build/assets').find(f => f.startsWith('app-') && f.endsWith('.js'));
const content = fs.readFileSync('public/build/assets/' + file, 'utf8');
console.log(content.match(/broadcaster:.*?\,/g));
console.log(content.match(/key:.*?\,/g));
