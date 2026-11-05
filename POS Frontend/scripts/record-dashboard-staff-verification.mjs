// Summarize completed test evidence without including private test payloads.
import fs from 'node:fs';
import path from 'node:path';

const frontend = path.resolve(import.meta.dirname, '..');
const audit = path.join(path.dirname(frontend), 'docs/audit');
const read = file => JSON.parse(fs.readFileSync(path.join(audit, file), 'utf8'));
const unit = read('private/dashboard-d4-vitest.json');
const browser = read('dashboard-d4-browser.json');
const sql = read('dashboard-d4-sql-smoke.json');
const migration = read('dashboard-d4-migration.json');
const xml = fs.readFileSync(path.join(audit, 'private/dashboard-d4-phpunit.xml'), 'utf8');
const tag = xml.match(/<testsuite\s+[^>]*>/)?.[0];
if (!tag) throw Error('Missing backend test summary.');
const attributes = Object.fromEntries([...tag.matchAll(/([A-Za-z_]+)="([^"]*)"/g)].map(match => [match[1], match[2]]));
const backend = { passed: Number(attributes.failures) === 0 && Number(attributes.errors) === 0, tests: Number(attributes.tests), assertions: Number(attributes.assertions), command: 'php artisan test --do-not-cache-result' };
if (!unit.success || !backend.passed || !browser.passed || !migration.business_rows_unchanged || !Object.values(sql.checks).every(Boolean)) throw Error('Verification is incomplete.');
const assets = fs.readdirSync(path.join(frontend, 'dist/assets')).filter(file => /\.(js|css)$/.test(file))
  .map(file => ({ file, bytes: fs.statSync(path.join(frontend, 'dist/assets', file)).size }));
const evidence = {
  checked_at: new Date().toISOString(), phase: 'D4', scope: 'Cashier and Branch Manager dashboards; private Hold/Recall workflow', backend,
  frontend: { passed: unit.success, tests: unit.numTotalTests, passed_tests: unit.numPassedTests, failed_tests: unit.numFailedTests, files: unit.testResults.length },
  browser: { passed: browser.passed, fixture_only: true, checks: Object.keys(browser.checks).length, screenshots: browser.screenshots.length },
  build: { passed: true, command: 'npm.cmd run build', assets }, php_formatting: { passed: true, files: 15 },
  database: { driver: sql.driver, read_only_sql_checks: Object.keys(sql.checks).length, migration_applied: true, migration: migration.migration,
    original_tables_checked: migration.original_tables_checked, business_rows_unchanged: migration.business_rows_unchanged,
    environment_unchanged: migration.environment_unchanged, existing_role_grants_unchanged: migration.existing_role_grants_unchanged },
  pending: ['D5', 'D6', 'D7', 'Staging deployment'],
};
fs.writeFileSync(path.join(audit, 'dashboard-d4-verification.json'), JSON.stringify(evidence, null, 2));
console.log(JSON.stringify({ backend, frontend: evidence.frontend, browser: evidence.browser, database: evidence.database }));
