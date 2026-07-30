const https = require('https');
const fs = require('fs');
const path = require('path');

const dir = path.join(__dirname, '..', 'public', 'frontend', 'images', 'hero');
fs.mkdirSync(dir, { recursive: true });

const map = [
  ['exams.svg', 'exams.svg', '#0d9488'],
  ['blogs.svg', 'blogging.svg', '#2563eb'],
  ['news.svg', 'newspaper.svg', '#ea580c'],
  ['questions.svg', 'questions.svg', '#7c3aed'],
  ['categories.svg', 'folder-files.svg', '#059669'],
  ['featured-exams.svg', 'certificate.svg', '#0f766e'],
  ['featured-blogs.svg', 'book-lover.svg', '#1d4ed8'],
  ['featured-news.svg', 'exciting-news.svg', '#c2410c'],
  ['featured-questions.svg', 'quiz.svg', '#6d28d9'],
  ['featured-categories.svg', 'bookmarks.svg', '#047857'],
];

function get(url) {
  return new Promise((resolve, reject) => {
    https
      .get(url, { headers: { 'User-Agent': 'Mozilla/5.0' } }, (res) => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
          return get(res.headers.location).then(resolve, reject);
        }
        if (res.statusCode !== 200) {
          reject(new Error(`${url} -> ${res.statusCode}`));
          res.resume();
          return;
        }
        const chunks = [];
        res.on('data', (c) => chunks.push(c));
        res.on('end', () => resolve(Buffer.concat(chunks)));
      })
      .on('error', reject);
  });
}

(async () => {
  for (const [out, src, color] of map) {
    const url = `https://cdn.jsdelivr.net/gh/balazser/undraw-svg-collection@main/svgs/${src}`;
    let svg = (await get(url)).toString('utf8');
    svg = svg.replace(/#6c63ff/gi, color);
    svg = svg.replace(/var\(--primary-svg-color\)/g, color);
    fs.writeFileSync(path.join(dir, out), svg);
    console.log('OK', out, color, svg.length);
  }

  fs.writeFileSync(
    path.join(dir, 'CREDITS.txt'),
    [
      'Hero illustrations based on unDraw (https://undraw.co).',
      'Source mirror: balazser/undraw-svg-collection (jsDelivr).',
      'Primary accent colors recolored to match Examtube module themes.',
      '',
    ].join('\n')
  );
})();
