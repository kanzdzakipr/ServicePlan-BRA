const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);
        if (stat && stat.isDirectory()) {
            if (!['node_modules', '.git', 'vendor'].includes(file)) {
                results = results.concat(walk(fullPath));
            }
        } else if (/\.(html|js|css|sql|php)$/i.test(file)) {
            results.push(fullPath);
        }
    });
    return results;
}

const files = walk('./');
let totalFixed = 0;

files.forEach(filePath => {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    content = content.split("¢â€ â€™").join(" &rarr; ");
    content = content.split("¢â¬â€œ").join(" - ");
    content = content.split("¢â¬â€").join(" - ");
    content = content.split("ƒâ€š©").join("&copy;");
    content = content.split("Ã‚").join("");
    content = content.split("¢â€").join(" - ");

    if (content !== original) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`[FIXED] Cleaned Mojibake in ${filePath}`);
        totalFixed++;
    }
});

console.log(`Done! Fixed ${totalFixed} files.`);
