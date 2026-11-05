import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { icons } from '../src/icons/catalog.js';
import { legacyIconClasses } from '../src/icons/legacy.js';

const frontend = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const backend = path.resolve(frontend, '..', 'POS backend');
const paths = Object.fromEntries(Object.entries(icons).map(([name, Icon]) => {
  const svg = renderToStaticMarkup(createElement(Icon));
  return [name, svg.slice(svg.indexOf('>') + 1, svg.lastIndexOf('</svg>'))];
}));
for (const name of Object.values(legacyIconClasses)) if (!paths[name]) throw new Error(`Missing Remix icon: ${name}`);
const packageRoot = path.join(frontend, 'node_modules', '@remixicon', 'react');
const { version } = JSON.parse(fs.readFileSync(path.join(packageRoot, 'package.json'), 'utf8'));
const notice = `/* Generated from @remixicon/react ${version}. Run npm run icons:sync in POS Frontend. See asset/licenses/remix-icon.txt. */\n`;
const runtime = fs.readFileSync(path.join(frontend, 'scripts', 'legacy-icon-runtime.js'), 'utf8');
const script = `${notice}(() => {\nconst remixPaths = ${JSON.stringify(paths)};\nconst remixAliases = ${JSON.stringify(legacyIconClasses)};\n${runtime}\n})();\n`;
let css = `${notice}
.remix-icon { display:inline-block; vertical-align:-.15em; flex-shrink:0; }
.remix-icon-holder { display:inline-flex!important; align-items:center; justify-content:center; font-style:normal; line-height:1; }
.remix-icon-holder::before { display:none!important; content:none!important; }
.remix-icon-holder > .remix-icon { width:1em; height:1em; vertical-align:0; pointer-events:none; }
.btn.remix-control-with-text > .remix-icon-holder { margin-inline-end:.35em; }
.remix-button-icon { margin-inline-end:.4em; pointer-events:none; }
.fa-fw { width:1.25em; text-align:center; }.fa-lg{font-size:1.3333em}.fa-2x{font-size:2em}.fa-3x{font-size:3em}
.fa-spin,.remix-icon-spin{animation:remix-icon-spin 1s linear infinite}
@keyframes remix-icon-spin{to{transform:rotate(360deg)}}
.nav-sidebar .menu-open>.nav-link .right.remix-icon-holder{transform:rotate(-90deg)}
@media(prefers-reduced-motion:reduce){.fa-spin,.remix-icon-spin{animation:none}}
`;
for (const [type, name] of Object.entries({ success: 'success', error: 'error', warning: 'warning', info: 'info' })) {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">${paths[name]}</svg>`;
  css += `#toast-container>.toast-${type}{background-image:url("data:image/svg+xml,${encodeURIComponent(svg)}")!important}\n`;
}
const outputs = {
  'public/asset/js/remix-icons.js': script,
  'public/asset/css/remix-icons.css': css,
  'public/asset/licenses/remix-icon.txt': fs.readFileSync(path.join(frontend, 'scripts', 'licenses', `remix-icon-${version}.txt`), 'utf8'),
};
for (const [relative, content] of Object.entries(outputs)) {
  const target = path.join(backend, relative); fs.mkdirSync(path.dirname(target), { recursive: true });
  fs.writeFileSync(target, content);
}
console.log(`Generated local Blade assets for ${Object.keys(paths).length} Remix icons (${version}).`);
