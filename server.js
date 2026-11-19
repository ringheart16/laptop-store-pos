const { spawn, exec } = require('child_process');
const os = require('os');
const path = require('path');
const fs = require('fs');

// Detect local LAN IP for mobile access
function getLocalIP() {
  const interfaces = os.networkInterfaces();
  for (const name of Object.keys(interfaces)) {
    for (const iface of interfaces[name]) {
      if (iface.family === 'IPv4' && !iface.internal) {
        return iface.address;
      }
    }
  }
  return 'localhost';
}

const localIP = getLocalIP();
const port = 8000;

console.log('\n======================================================');
console.log('  LAPTOP STORE MANAGEMENT SYSTEM');
console.log('  Native PHP • Tailwind CSS • Node.js Pipeline');
console.log('======================================================\n');

// 1. Build Tailwind CSS
console.log('>> Compiling Tailwind CSS...');
try {
  require('child_process').execSync('npm run build:css', { stdio: 'inherit', cwd: __dirname });
} catch (e) {
  console.log('Tailwind build notice:', e.message);
}

// 2. Locate PHP binary
let phpBin = 'php';
if (fs.existsSync('C:\\xampp\\php\\php.exe')) {
  phpBin = 'C:\\xampp\\php\\php.exe';
}

console.log(`\n>> Starting PHP Server with ${phpBin}...`);

const phpServer = spawn(phpBin, ['-S', `0.0.0.0:${port}`, '-t', __dirname], {
  stdio: 'inherit'
});

console.log('\n------------------------------------------------------');
console.log(`  Local PC Browser:    http://localhost:${port}/`);
console.log(`  XAMPP Apache URL:    http://localhost/laptop/`);
console.log(`  Mobile / Tablet:     http://${localIP}:${port}/`);
console.log('------------------------------------------------------\n');

// Auto-open browser
const openCmd = process.platform === 'win32' ? 'start' : (process.platform === 'darwin' ? 'open' : 'xdg-open');
exec(`${openCmd} http://localhost/laptop/`);

phpServer.on('close', (code) => {
  console.log(`Server stopped (code ${code})`);
});
