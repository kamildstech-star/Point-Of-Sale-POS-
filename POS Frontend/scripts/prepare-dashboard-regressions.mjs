// Reuse the already reviewed role/POS checks without overwriting UX-5 evidence.
import fs from 'node:fs';
for (const [type, folder] of [['role', 'roles'], ['pos', 'pos']]) {
  const original = new URL(`./refinement-ux5-${type}-browser-smoke.mjs`, import.meta.url);
  const source = fs.readFileSync(original, 'utf8')
    .replaceAll(`docs/design/refinement/ux5/${folder}`, `docs/design/dashboard-d5-d7/regression/${folder}`)
    .replaceAll(`refinement-ux5-${type}-`, `dashboard-d7-${type}-`)
    .replaceAll('!b.disabled', "!b.matches(':disabled')")
    .replace("async function quote() { await click('Get server quote');", "async function quote() { await until('[...document.querySelectorAll(\"button\")].some(e=>e.textContent.trim()===\"Get server quote\"&&!e.matches(\":disabled\"))'); await click('Get server quote');")
    .replace('async function add() { await evaluate', 'async function add() { await until(\'!!document.querySelector(".product-choice:not(:disabled)")\'); await evaluate');
  fs.writeFileSync(new URL(`./dashboard-d7-${type}-browser-smoke.mjs`, import.meta.url), source);
}
