const fs = require('fs');
const path = require('path');

const source = path.join(__dirname, 'public', 'Lambang_Kabupaten_Bogor.png');
const dest = path.join(__dirname, 'resources', 'images', 'Lambang_Kabupaten_Bogor.png');

// Ensure destination directory exists
const destDir = path.dirname(dest);
if (!fs.existsSync(destDir)) {
    fs.mkdirSync(destDir, { recursive: true });
}

// Copy file
fs.copyFileSync(source, dest);
console.log('Logo berhasil di-copy ke resources/images/');

