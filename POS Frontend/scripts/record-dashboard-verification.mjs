import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend), docs = path.join(root, 'docs'), audit = path.join(docs, 'audit');
const readJson = name => JSON.parse(fs.readFileSync(path.join(audit, name), 'utf8'));
const react = readJson('dashboard-d5-d7-react.json');
if (!react.success || react.numFailedTests || react.numPassedTests !== react.numTotalTests) throw Error('The complete React suite must pass.');
const suite = fs.readFileSync(path.join(audit, 'dashboard-d5-d7-laravel.xml'), 'utf8').match(/<testsuite\s[^>]+>/)?.[0];
const attribute = name => Number(suite?.match(new RegExp(name + '="([0-9.]+)"'))?.[1]);
if (!suite || attribute('failures') || attribute('errors') || attribute('skipped') || attribute('tests') < 65) throw Error('The Laravel dashboard suite must pass without skips.');
const groups = [
  { title: 'Before — verified UX-5 summaries', report: 'dashboard-d5-d7-baseline-before.json', dir: 'baseline/before', baseline: true },
  { title: 'D5/D6 — specialists, personal sales and platform', report: 'dashboard-d5-d7-browser.json', dir: 'after' },
  { title: 'D7 — existing roles and custom permissions', report: 'dashboard-d7-role-after.json', dir: 'regression/roles/after' },
  { title: 'D7 — cashier and checkout regressions', report: 'dashboard-d7-pos-after.json', dir: 'regression/pos/after' },
];
const builtAt = fs.statSync(path.join(frontend, 'dist/index.html')).mtimeMs;
for (const group of groups) {
  group.result = readJson(group.report);
  if (!group.result.passed || !group.result.fixture_only || group.result.working_database_writes !== 0) throw Error('Invalid browser evidence: ' + group.report);
  if (!group.baseline && fs.statSync(path.join(audit, group.report)).mtimeMs < builtAt) throw Error('Browser evidence predates final build: ' + group.report);
  for (const file of group.result.screenshots) if (!fs.existsSync(path.join(docs, 'design/dashboard-d5-d7', group.dir, file))) throw Error('Missing screenshot: ' + file);
}
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
function filesIn(dir) { return fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => entry.isDirectory() ? filesIn(path.join(dir, entry.name)) : [path.join(dir, entry.name)]); }
const sources = [
  ...filesIn(path.join(frontend, 'src')),
  ...filesIn(path.join(root, 'POS backend/app/Domain/Dashboard')),
  ...['app/Http/Controllers/Api/V1/DashboardController.php','app/Http/Controllers/Api/V1/DashboardContextController.php','app/Http/Controllers/Api/V1/PlatformDashboardController.php','app/Http/Resources/StaffResource.php','config/dashboard.php','routes/api.php','tests/Feature/SpecialistDashboardTest.php'].map(file => path.join(root, 'POS backend', file)),
].sort();
for (const file of sources.filter(file => file.startsWith(path.join(frontend, 'src')) && !file.includes(path.sep + 'test' + path.sep))) if (fs.statSync(file).mtimeMs > builtAt) throw Error('Source changed after build: ' + file);
const assets = fs.readdirSync(path.join(frontend, 'dist/assets')).filter(file => /\.(js|css)$/.test(file)).map(name => { const file = path.join(frontend, 'dist/assets', name); return { name, bytes: fs.statSync(file).size, sha256: hash(file) }; });
const entry = assets.find(asset => /^index-.*\.js$/.test(asset.name)), prior = readJson('refinement-ux5-verification.json').performance.initial_js_after;
if (!entry || !assets.some(a => a.name.startsWith('PlatformWorkspace-')) || !assets.some(a => a.name.startsWith('SalesAssociateDashboard-'))) throw Error('Missing lazy dashboard chunks.');
const current = groups.filter(group => !group.baseline), checks = current.reduce((sum, group) => sum + Object.keys(group.result.checks).length, 0);
const report = {
  phase: 'Dashboard D5–D7', verified_on: '2026-11-06', passed: true,
  scope: 'D5/D6 engineering and D7 local verification/rollout preparation; live pilot and human acceptance pending.',
  working_database_writes: 0, new_migrations: 0, live_deployment: false, specialist_default_enabled: false, platform_default_enabled: false,
  react: { tests: react.numTotalTests, files: react.testResults.length, passed: react.numPassedTests, failed: 0 },
  laravel: { tests: attribute('tests'), assertions: attribute('assertions'), failures: 0, errors: 0, isolated_database: 'SQLite :memory:' },
  browser: { checks, groups: current.map(g => ({ title: g.title, checks: Object.keys(g.result.checks).length, screenshots: g.result.screenshots.length, evidence: g.report })), screenshots: current.reduce((sum,g) => sum + g.result.screenshots.length, 0), baseline_screenshots: groups[0].result.screenshots.length, widths: [360,768,1280,1440], themes: ['light','dark'], fixture_only: true },
  production_build: { passed: true, initial_js: entry, prior_ux5_initial_js: prior, bytes_added_to_initial_js: entry.bytes - prior.bytes, note: 'Bundle bytes, not a production latency or MySQL performance benchmark. Specialist/platform views load on demand.', assets },
  manual_acceptance: { new_uat_cases: 20, status: 'Not Run', role_signoff: 'Pending', pilot_feedback: 'Pending', staging: 'No staging server supplied', devices: 'Pending', representative_mysql_performance: 'Pending' },
  source_files: sources.map(file => ({ path: path.relative(root, file).replaceAll('\\','/'), sha256: hash(file) })),
};
const escape = value => String(value).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
const gallery = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dashboard D5–D7 review</title><style>body{margin:0;background:#f5f7fb;color:#14253e;font:15px/1.6 system-ui,sans-serif}main{max-width:1440px;margin:auto;padding:24px}a{color:#174fc4}h1{font-size:28px}h2{margin-top:40px}.notice{padding:16px;background:white;border:1px solid #cbd6e7;border-radius:10px}.shots{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:18px}figure{margin:0;border:1px solid #cbd6e7;border-radius:10px;overflow:hidden;background:white}img{width:100%;height:290px;object-fit:contain;object-position:top;background:#e7edf6}figcaption{padding:12px}a:focus-visible{outline:3px solid #174fc4;outline-offset:3px}@media(prefers-color-scheme:dark){body{background:#0b1220;color:#e2e8f0}.notice,figure{background:#151f31;border-color:#52627b}a{color:#b4c7ff}}</style></head><body><main><h1>Universal POS — Dashboard D5–D7</h1><p>Verified 2026-11-06 · synthetic screenshots only · no working-database writes.</p><div class="notice"><strong>${react.numTotalTests} React tests · ${attribute('tests')} Laravel tests / ${attribute('assertions')} assertions · ${checks} browser checks · production build passed.</strong><p>D5/D6 and local D7 engineering are ready for review. Pilot deployment, devices, feedback and human sign-off are pending. New specialist/platform pilots default off.</p><a href="../../DASHBOARD_D5_D6_D7.md">Implementation and rollout guide</a> · <a href="../../DASHBOARD_ROLE_SIGNOFF.md">Role acceptance matrix</a> · <a href="../../audit/dashboard-d5-d7-verification.json">Verification manifest</a></div>${groups.map(g => `<section><h2>${escape(g.title)}</h2><p><a href="../../audit/${g.report}">Machine-readable evidence</a></p><div class="shots">${g.result.screenshots.map(file => `<figure><a href="${g.dir}/${escape(file)}"><img loading="lazy" src="${g.dir}/${escape(file)}" alt="${escape(file.replace('.png','').replaceAll('-',' '))}"></a><figcaption>${escape(file.replace('.png','').replaceAll('-',' '))}</figcaption></figure>`).join('')}</div></section>`).join('')}</main></body></html>`;
for (const match of gallery.matchAll(/(?:href|src)="([^"]+)"/g)) {
  const target = path.resolve(docs, 'design/dashboard-d5-d7', match[1]);
  if (match[1].endsWith('dashboard-d5-d7-verification.json')) continue;
  if (!target.startsWith(docs + path.sep) || !fs.existsSync(target)) throw Error('Broken gallery link: ' + match[1]);
}
fs.writeFileSync(path.join(audit, 'dashboard-d5-d7-verification.json'), JSON.stringify(report, null, 2));
fs.writeFileSync(path.join(docs, 'design/dashboard-d5-d7/index.html'), gallery);
console.log(JSON.stringify({ passed: true, react: report.react.tests, laravel: report.laravel.tests, assertions: report.laravel.assertions, browser_checks: checks, current_screenshots: report.browser.screenshots, baseline_screenshots: report.browser.baseline_screenshots, verified_gallery_links: [...gallery.matchAll(/(?:href|src)="([^"]+)"/g)].length }));
