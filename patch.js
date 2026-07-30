const fs = require('fs');
let css = fs.readFileSync('assets/css/style.css', 'utf8');

css = css.replace('.footer ul li {\r\n    margin-bottom: 0.8rem;\r\n    color: #ccc;', '.footer ul li {\r\n    margin-bottom: 0.8rem;\r\n    color: #fff;');
css = css.replace('.footer ul li {\n    margin-bottom: 0.8rem;\n    color: #ccc;', '.footer ul li {\n    margin-bottom: 0.8rem;\n    color: #fff;');

css = css.replace('.nav-links {\r\n    display: flex;\r\n    gap: 2.5rem;\r\n}', '.nav-links {\r\n    display: flex;\r\n    gap: 2.5rem;\r\n    position: absolute;\r\n    left: 50%;\r\n    transform: translateX(-50%);\r\n}');
css = css.replace('.nav-links {\n    display: flex;\n    gap: 2.5rem;\n}', '.nav-links {\n    display: flex;\n    gap: 2.5rem;\n    position: absolute;\n    left: 50%;\n    transform: translateX(-50%);\n}');

css = css.replace('text-shadow: 0 2px 20px rgba(0,0,0,0.3);\r\n}', 'text-shadow: 0 2px 20px rgba(0,0,0,0.3);\r\n    letter-spacing: 8px;\r\n}');
css = css.replace('text-shadow: 0 2px 20px rgba(0,0,0,0.3);\n}', 'text-shadow: 0 2px 20px rgba(0,0,0,0.3);\n    letter-spacing: 8px;\n}');

css = css.replace('letter-spacing: 3px;\r\n    margin-bottom: 2rem;', 'letter-spacing: 5px;\r\n    margin-bottom: 2rem;');
css = css.replace('letter-spacing: 3px;\n    margin-bottom: 2rem;', 'letter-spacing: 5px;\n    margin-bottom: 2rem;');

fs.writeFileSync('assets/css/style.css', css);
console.log('Patched');