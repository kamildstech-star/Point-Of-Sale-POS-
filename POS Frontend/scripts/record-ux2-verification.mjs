import fs from 'node:fs';
import path from 'node:path';
const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend);
const read = file => JSON.parse(fs.readFileSync(path.join(root, file), 'utf8').replace(/^\uFEFF/, ''));
const unitFile = 'docs/audit/private/refinement-ux2-vitest.json';
const coreFile = 'docs/audit/refinement-ux2-after.json', staffFile = 'docs/audit/refinement-ux2-staff-browser.json';
const unit = read(unitFile), core = read(coreFile), staff = read(staffFile);
if (unit.numFailedTests || unit.numPassedTests < 229 || !core.passed || !staff.passed) throw Error('UX-2 checks incomplete');
for (const [folder, files] of [['after', core.screenshots], ['staff', staff.screenshots]]) {
  for (const file of files) if (!fs.existsSync(path.join(root, 'docs/design/refinement/ux2', folder, file))) throw Error(`Missing screenshot: ${folder}/${file}`);
}
for (const [file, report] of [[coreFile, core], [staffFile, staff]]) {
  if (!Object.values(report.checks).every(Boolean) || report.working_database_writes !== 0) throw Error(`Invalid evidence: ${file}`);
  if (fs.statSync(path.join(root, file)).mtimeMs < fs.statSync(path.join(frontend, 'dist/index.html')).mtimeMs) throw Error(`Browser report predates final build: ${file}`);
}
const assets = fs.readdirSync(path.join(frontend, 'dist/assets')).filter(name => /^index-.*\.(js|css)$/.test(name)).map(name => ({ name, bytes: fs.statSync(path.join(frontend, 'dist/assets', name)).size }));
const summary = {
  phase: 'UX-2 core', verified_on: '2026-11-05', timezone: 'Asia/Karachi', passed: true,
  react: { tests: unit.numPassedTests, files: unit.testResults.length, failed: unit.numFailedTests },
  browser: { core_checks: Object.keys(core.checks).length, staff_checks: Object.keys(staff.checks).length, total_checks: Object.keys(core.checks).length + Object.keys(staff.checks).length, core_screenshots: core.screenshots.length, staff_screenshots: staff.screenshots.length, total_screenshots: core.screenshots.length + staff.screenshots.length, copied_baselines: 6 },
  production_build: { passed: true, assets, new_dependencies: 0 },
  fixture_only: true, working_database_writes: 0, backend_changes: false,
  evidence: { react: unitFile, core: coreFile, staff: staffFile },
  limitations: ['Browser previews use synthetic HTTP fixtures, not the working Laravel database.', 'No physical scanner testing or fresh live-backend isolation audit.', 'Optional table density/preferences and UX-3+ are not included.'],
};
fs.writeFileSync(path.join(root, 'docs/audit/refinement-ux2-verification.json'), JSON.stringify(summary, null, 2));

const gallery = path.join(root, 'docs/design/refinement/ux2');
const escape = value => value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
const title = file => file.replace(/\.png$/, '').replaceAll('-', ' ');
const figure = (folder, file) => `<figure data-kind="${folder}" data-theme="${file.includes('-dark') ? 'dark' : file.includes('-light') ? 'light' : 'other'}"><a href="${folder}/${file}"><img loading="lazy" src="${folder}/${file}" alt="${escape(title(file))}" width="1440" height="1000"></a><figcaption>${escape(title(file))}</figcaption></figure>`;
const comparisons = ['products-light-1440.png', 'products-dark-1440.png', 'product-form-light-1440.png', 'product-form-dark-1440.png', 'products-light-360.png', 'product-form-light-360.png'];
const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Universal POS — UX-2 review</title>
<style>:root{font-family:system-ui,sans-serif;color:#1e293b;background:#f5f7fb}body{max-width:1500px;margin:0 auto;padding:24px}h1{font-size:26px}h2{font-size:20px;margin-top:32px}a{color:#1d4ed8}p{line-height:1.6}header,.controls{padding:16px;background:white;border:1px solid #e2e8f0;border-radius:8px}.controls{display:flex;gap:16px;flex-wrap:wrap;position:sticky;top:0;z-index:1}select{padding:8px;font:inherit}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:16px}figure{margin:0;border:1px solid #e2e8f0;border-radius:8px;background:white;overflow:hidden}figure[hidden]{display:none}img{display:block;max-width:100%;width:100%;height:280px;object-fit:contain;background:#e2e8f0}figcaption{padding:12px;font-size:13px}details{margin:24px 0}summary{cursor:pointer;padding:12px;background:white}footer{padding:24px 0}a:focus-visible,select:focus-visible,summary:focus-visible{outline:3px solid #2563eb;outline-offset:3px}</style></head><body>
<header><h1>UX-2 — Listings & forms</h1><p>Approved core refinements to the existing Universal POS. Click an image to view it at full resolution. Screenshots use synthetic data and perform zero writes to the working database.</p><p><strong>${summary.react.tests} React tests · ${summary.browser.total_checks} browser checks · ${summary.browser.total_screenshots} current screenshots</strong></p><p><a href="../../../UX2_REFINEMENT.md">Implementation notes</a> · <a href="../../../audit/refinement-ux2-verification.json">Verification summary</a> · <a href="../ux1/index.html">UX-1 gallery</a></p></header>
<h2>Before and after</h2><p>Each pair shows the verified UX-1 baseline followed by UX-2. The new fixture contains 45 records so server paging and table scrolling can be exercised.</p>
${comparisons.map(file => `<details${file === comparisons[0] ? ' open' : ''}><summary>${escape(title(file))}</summary><div class="grid">${figure('before', file)}${figure('after', file)}</div></details>`).join('\n')}
<h2>Current screens</h2><div class="controls"><label>Theme <select id="theme"><option value="all">Both themes</option><option value="light">Light</option><option value="dark">Dark</option></select></label><label>Area <select id="area"><option value="all">All screens</option><option value="after">Lists & forms</option><option value="staff">Cashier & manager regression</option></select></label></div>
<div class="grid" id="screens">${core.screenshots.map(file => figure('after', file)).join('\n')}${staff.screenshots.map(file => figure('staff', file)).join('\n')}</div>
<footer>Widths 360/768/1280/1440/1920; effective reflow at 125% and 200%. Optional table density preference and subsequent UX phases remain outside this approval.</footer>
<script>function filter(){const theme=document.getElementById('theme').value,area=document.getElementById('area').value;document.querySelectorAll('#screens figure').forEach(f=>f.hidden=(theme!=='all'&&f.dataset.theme!==theme&&f.dataset.theme!=='other')||(area!=='all'&&f.dataset.kind!==area))}document.querySelectorAll('select').forEach(s=>s.addEventListener('change',filter));</script></body></html>`;
fs.writeFileSync(path.join(gallery, 'index.html'), html);
console.log(JSON.stringify(summary, null, 2));
