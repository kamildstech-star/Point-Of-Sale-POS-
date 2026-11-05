import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(import.meta.dirname, '../..'), audit = path.join(root, 'docs/audit');
const read = name => JSON.parse(fs.readFileSync(path.join(audit, name), 'utf8').replace(/^\uFEFF/, ''));
const full = read('private/refinement-ux1-vitest.json');
const focused = read('private/refinement-ux1-dashboard-vitest.json');
const browser = read('refinement-ux1-after.json');
const staff = read('refinement-ux1-staff-browser.json');
if (![full, focused].every(r => r.success && r.numFailedTests === 0) || ![browser, staff].every(r => r.passed && Object.values(r.checks).every(value => value === true))) throw Error('Verification is not complete.');
if (!browser.checks.light_readable_chart_labels || !browser.checks.dark_readable_chart_labels) throw Error('Final chart-label verification is missing.');
const assets = fs.readdirSync(path.join(root, 'POS Frontend/dist/assets')).filter(name => /^index-.*\.(css|js)$/.test(name)).map(name => ({ name, bytes: fs.statSync(path.join(root, 'POS Frontend/dist/assets', name)).size }));
const result = {
  phase: 'UX-1', verified_on: '2026-11-05', passed: true,
  react: { passed: full.numPassedTests, files: full.testResults.length },
  focused_dashboard_regression_after_responsive_chart_change: { passed: focused.numPassedTests, files: focused.testResults.length },
  browser: { ui_checks: Object.keys(browser.checks).length, staff_checks: Object.keys(staff.checks).length, screenshots: browser.screenshots.length + staff.screenshots.length, synthetic_http_fixtures: true },
  production_build: { command: 'npm.cmd run build', passed: true, assets },
  working_database_writes: 0, backend_changes: false,
  pending_review: ['UX-2', 'UX-3', 'UX-4', 'UX-5'],
};
fs.writeFileSync(path.join(audit, 'refinement-ux1-verification.json'), JSON.stringify(result, null, 2));
console.log(JSON.stringify(result));
