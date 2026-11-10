// Built application + synthetic HTTP fixtures only; no working database access.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { specialistCases, specialistTenant, specialistFixture, platformFixture } from '../src/test/fixtures/specialistDashboard.js';
const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend);
const output = path.join(root, 'docs/design/dashboard-d5-d7/after'), audit = path.join(root, 'docs/audit'), privateDir = path.join(audit, 'private');
fs.mkdirSync(output, { recursive: true }); fs.mkdirSync(privateDir, { recursive: true });
let kind = 'inventory', tenant = specialistTenant(kind), mode = 'ready';
const requests = [], errors = [], screenshots = [], checks = {}, timings = {};
function user() { return { id: 83, name: 'Synthetic ' + kind, permissions: kind === 'platform' ? [] : tenant.permissions, platform_permissions: kind === 'platform' ? ['dashboard.platform.view'] : [], platform_dashboard_enabled: kind === 'platform', active_tenant: kind === 'platform' ? null : { id: tenant.id, name: tenant.name, timezone: tenant.timezone, currency_code: tenant.currency } }; }
const page = rows => ({ data: rows, total: rows.length, current_page: 1, last_page: 1 });
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://fixture.test');
    if (url.pathname.startsWith('/api/')) {
      requests.push({ method: req.method, path: url.pathname + url.search }); res.setHeader('Content-Type', 'application/json');
      const api = url.pathname.slice('/api/v1/'.length) + url.search;
      if (mode === 'slow' && api.startsWith('dashboard/')) await new Promise(resolve => setTimeout(resolve, 1500));
      let result;
      if (api === 'auth/me') result = { data: user() };
      else if (api === 'tenants') result = page([{ id: 1, name: tenant.name }]);
      else if (api === 'me/context') result = { data: { active_tenant: tenant } };
      else if (api === 'platform/context') {
        if (mode === 'denied') { res.statusCode = 403; result = { message: 'Synthetic platform permission revoked.' }; }
        else result = { data: { user_id: 83, scope: 'platform', permissions: ['dashboard.platform.view'], dashboard_enabled: mode !== 'disabled' } };
      } else if (api.startsWith('platform/dashboard')) result = platformFixture(api);
      else if (api.startsWith('dashboard/')) {
        const resource = url.pathname.split('/').at(-1);
        if (mode === 'error' && resource === 'inventory-trend') { res.statusCode = 503; result = { message: 'Synthetic movement temporarily unavailable.' }; }
        else if (mode === 'denied') { res.statusCode = 403; result = { message: 'Synthetic dashboard access revoked.' }; }
        else { result = specialistFixture(api, tenant); if (mode === 'empty' && resource === 'inventory-trend') result.data.items = []; }
      } else if (api.startsWith('catalog/locations')) result = page([{ id: 3, branch_id: 1, name: 'Main stock' }]);
      else if (api.startsWith('operations/suppliers')) result = page([{ id: 5, name: 'Synthetic supplier' }]);
      else result = page([]);
      res.end(JSON.stringify(result)); return;
    }
    const dist = path.join(frontend, 'dist'), candidate = path.resolve(dist, decodeURIComponent(url.pathname).replace(/^\/+/, ''));
    if (candidate !== dist && !candidate.startsWith(dist + path.sep)) { res.statusCode = 403; res.end(); return; }
    const file = fs.existsSync(candidate) && fs.statSync(candidate).isFile() ? candidate : path.join(dist, 'index.html');
    res.setHeader('Content-Type', ({ '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.woff': 'font/woff', '.woff2': 'font/woff2' })[path.extname(file)] || 'application/octet-stream'); res.end(fs.readFileSync(file));
  } catch (failure) { errors.push(failure.message); res.statusCode = 500; res.end('{}'); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`, profile = fs.mkdtempSync(path.join(privateDir, 'dashboard-d7-chrome-'));
const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let socket, sequence = 0, stage = 'startup'; const pending = new Map();
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function send(method, params = {}) { const id = ++sequence; return new Promise((resolve, reject) => { const timer = setTimeout(() => { pending.delete(id); reject(Error(`CDP timeout: ${method}`)); }, 30000); pending.set(id, { resolve: result => { clearTimeout(timer); resolve(result); }, reject }); socket.send(JSON.stringify({ id, method, params })); }); }
async function evaluate(expression) { const r = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (r.exceptionDetails) throw Error(r.exceptionDetails.exception?.description || r.exceptionDetails.text); return r.result.value; }
async function until(expression) { const start = Date.now(); while (Date.now() - start < 20000) { if (await evaluate(expression)) return; await delay(100); } throw Error(`Timed out: ${expression}`); }
async function navigate(route = '/', selector = '.business-dashboard') { await evaluate('window.__dashboardNavigationPending=true'); const started = Date.now(); await send('Page.navigate', { url: origin + route }); await until(`!window.__dashboardNavigationPending&&document.readyState==='complete'&&!!document.querySelector(${JSON.stringify(selector)})`); await evaluate('document.fonts.ready'); timings[stage] = Date.now() - started; }
async function viewport(width = 1440, zoom = 1) { await send('Emulation.setDeviceMetricsOverride', { width: Math.round(width / zoom), height: Math.round(1000 / zoom), deviceScaleFactor: zoom, mobile: false }); await evaluate('window.scrollTo(0,0)'); await delay(120); }
async function capture(name) { await evaluate('[...document.querySelectorAll(".Toastify__close-button")].forEach(button=>button.click())'); await delay(350); const r = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); fs.writeFileSync(path.join(output, name + '.png'), Buffer.from(r.data, 'base64')); screenshots.push(name + '.png'); }
async function assert(expression, name) { if (!await evaluate(expression)) throw Error(name); checks[name] = true; }
async function click(label) { const b = await evaluate(`(()=>{const e=[...document.querySelectorAll('button,a')].find(e=>!e.matches(':disabled')&&!e.closest('[inert],[aria-hidden="true"]')&&e.getBoundingClientRect().width&&(e.getAttribute('aria-label')===${JSON.stringify(label)}||e.textContent.trim()===${JSON.stringify(label)}));if(!e)throw Error('Missing control: '+${JSON.stringify(label)});e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2};})()`); await send('Input.dispatchMouseEvent', { type: 'mousePressed', button: 'left', clickCount: 1, ...b }); await send('Input.dispatchMouseEvent', { type: 'mouseReleased', button: 'left', clickCount: 1, ...b }); }
async function key(key, code = key) { await send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, ...(key === 'Enter' ? { text: '\r' } : {}) }); await send('Input.dispatchKeyEvent', { type: 'keyUp', key, code }); }
async function contrast(name) { const failures = await evaluate(`(()=>{const rgb=c=>c.match(/[\\d.]+/g).slice(0,3).map(Number),lum=c=>rgb(c).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);return [...document.querySelectorAll('main h1,main h2,main h3,main p,main label,main th,main td,main button,main a')].filter(e=>e.getBoundingClientRect().width&&!e.matches(':disabled')).map(e=>{const c=getComputedStyle(e);let p=e,bg=c.backgroundColor;while(bg==='rgba(0, 0, 0, 0)'&&p.parentElement){p=p.parentElement;bg=getComputedStyle(p).backgroundColor;}const a=lum(c.color),b=lum(bg);return {text:e.textContent.slice(0,50),ratio:(Math.max(a,b)+.05)/(Math.min(a,b)+.05)};}).filter(e=>e.ratio<4.5);})()`); if (failures.length) throw Error(name + ': ' + JSON.stringify(failures)); checks[name] = true; }
try {
  const endpoint = await new Promise((resolve, reject) => { let text = ''; const timer = setTimeout(() => reject(Error('Chrome startup timeout')), 20000); chrome.on('error', reject); chrome.stderr.on('data', chunk => { text += chunk; const match = text.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timer); resolve(match[1]); } }); });
  const targets = await (await fetch(endpoint.replace('ws:', 'http:').replace(/\/devtools\/browser\/.*/, '/json/list'))).json(); socket = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
  socket.addEventListener('message', event => { const m = JSON.parse(event.data); if (m.id) { const task = pending.get(m.id); if (task) { pending.delete(m.id); m.error ? task.reject(Error(m.error.message)) : task.resolve(m.result); } } else if (m.method === 'Runtime.exceptionThrown') errors.push(m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text); });
  await send('Page.enable'); await send('Runtime.enable'); await viewport();
  for (const name of [...Object.keys(specialistCases), 'platform']) {
    kind = name; if (name !== 'platform') tenant = specialistTenant(name); mode = 'ready'; const start = requests.length;
    for (const theme of ['light', 'dark']) {
      stage = name + '-' + theme; await viewport(); await navigate(name === 'platform' && theme === 'dark' ? '/platform/dashboard' : '/');
      if (await evaluate('document.documentElement.dataset.theme!==' + JSON.stringify(theme))) await click(theme === 'dark' ? 'Switch to dark theme' : 'Switch to light theme');
      await assert('document.querySelector("main h1").textContent===' + JSON.stringify(name === 'platform' ? 'Platform dashboard' : specialistCases[name].title), stage + '_title');
      await contrast(stage + '_contrast');
      await assert('[...document.querySelectorAll("main button")].filter(e=>e.getBoundingClientRect().width).every(e=>e.textContent.trim()||e.getAttribute("aria-label"))', stage + '_button_names');
      await assert('[...document.querySelectorAll(".trend-chart svg")].every(e=>e.getAttribute("aria-labelledby")?.split(" ").every(id=>document.getElementById(id)))', stage + '_chart_labels');
      for (const width of [1440, 360, 768, 1280]) { await viewport(width); await assert('document.documentElement.scrollWidth<=innerWidth+1', stage + '_' + width + '_reflows'); if ([1440, 360].includes(width)) await capture(stage + '-' + width); }
      await viewport(1440, 2); await assert('document.documentElement.scrollWidth<=innerWidth+1', stage + '_zoom_200_reflows');
      await viewport();
      const heading = { inventory: 'Stock movement', purchasing: 'Purchase order status and open orders', accounting: 'Revenue and expenses', associate: 'Your recent receipts', platform: 'Processing exceptions' }[name];
      await evaluate('(()=>{const e=[...document.querySelectorAll("main h2")].find(e=>e.textContent===' + JSON.stringify(heading) + ');e.scrollIntoView({block:"start"});window.scrollBy(0,-82);})()'); await capture(stage + '-details');
      if (name !== 'purchasing') {
        await evaluate('document.querySelector(".trend-chart summary").focus()'); await key('Enter');
        await assert('document.querySelector(".trend-chart details").open', stage + '_keyboard_chart_data');
        await assert('!!document.querySelector(".trend-chart details[open] caption")', stage + '_chart_table_caption');
      }
    }
    const resources = [...new Set(requests.slice(start).filter(r => r.path.startsWith('/api/v1/dashboard/')).map(r => r.path.split('?')[0].split('/').at(-1)))].sort();
    const expected = { inventory: ['summary','alerts','inventory-trend'], purchasing: ['summary','purchase-orders','supplier-overview'], accounting: ['summary','accounting-trend','accounting-exceptions'], associate: ['personal-sales'], platform: [] }[name].sort();
    if (JSON.stringify(resources) !== JSON.stringify(expected)) throw Error(name + ': unexpected resource plan ' + resources); checks[name + '_planned_resources_only'] = true;
    if (name === 'platform') {
      if (requests.slice(start).some(r => !r.path.startsWith('/api/v1/platform/') && r.path !== '/api/v1/auth/me')) throw Error('Platform requested tenant data');
      checks.platform_no_tenant_requests = true; await assert('!document.querySelector("select[aria-label=Branch]")&&!document.querySelector(".tenant-switcher")', 'platform_no_tenant_switcher');
    }
  }
  stage = 'inventory-interactions'; kind = 'inventory'; tenant = specialistTenant(kind); await navigate();
  await evaluate('(()=>{const e=document.querySelector("select[aria-label=Branch]");e.value="2";e.dispatchEvent(new Event("change",{bubbles:true}));})()');
  await until('document.querySelector(".dashboard-context")?.textContent.includes("City Branch")');
  await assert('[...document.querySelectorAll("a")].find(e=>e.textContent.trim()==="Stock adjustment").getAttribute("href").includes("branch_id=2")', 'adjustment_keeps_branch');
  await evaluate('(()=>{const n=[...document.querySelectorAll("nav")].find(e=>e.getAttribute("aria-label")==="Movement unit results");[...n.querySelectorAll("button")].find(e=>e.textContent.trim()==="Next").click();})()');
  await until('document.querySelector(".specialist-widgets select")?.value==="2"'); checks.movement_server_pagination = true;
  await click('Stock adjustment'); await until('!!document.querySelector("[role=dialog] input[name=reason]")'); await capture('adjustment-modal'); await key('Escape'); await until('!document.querySelector("[role=dialog]")'); checks.adjustment_existing_modal = true;
  stage = 'purchasing-form'; kind = 'purchasing'; tenant = specialistTenant(kind); await navigate(); await click('Create purchase order'); await until('!!document.querySelector("[role=dialog] select[name=supplier_id]")'); await capture('purchase-order-modal'); await key('Escape'); checks.purchase_existing_modal = true;
  stage = 'empty'; kind = 'inventory'; tenant = specialistTenant(kind); mode = 'empty'; await navigate(); await assert('document.querySelector("main").textContent.includes("No posted stock movement")', 'inventory_empty'); await capture('inventory-empty');
  stage = 'error'; mode = 'error'; await navigate(); await assert('document.querySelector("main").textContent.includes("Synthetic movement temporarily unavailable.")', 'local_error'); await capture('inventory-error'); mode = 'ready'; await click('Try again'); await until('!!document.querySelector(".specialist-widgets select")'); checks.retry_rechecks_context = true;
  stage = 'slow'; mode = 'slow'; await navigate('/', 'main[aria-busy=true]'); await until('!!document.querySelector("main[aria-busy=true] [role=status]")'); await assert('document.querySelector("main[aria-busy=true] [role=status]").getAttribute("aria-label")==="Loading business dashboard…"', 'slow_request_named_loader'); await capture('inventory-slow'); await until('!!document.querySelector(".specialist-widgets select")'); mode = 'ready';
  stage = 'rollback'; tenant.dashboard.allowed_widgets = ['inventory','alerts']; tenant.dashboard.specialist_enabled = false; const start = requests.length; await navigate(); await assert('!document.querySelector(".specialist-widgets")', 'pilot_rollback_old_summary'); if (requests.slice(start).some(r => r.path.includes('/dashboard/inventory-trend'))) throw Error('Rollback requested pilot resources'); checks.rollback_no_new_requests = true; await capture('inventory-rollback');
  stage = 'platform-disabled'; kind = 'platform'; mode = 'disabled'; await navigate('/platform/dashboard', 'main'); await until('document.querySelector("main")?.textContent.includes("Platform pilot is not enabled")'); await capture(stage); checks.platform_disabled = true;
  stage = 'platform-revoked'; mode = 'denied'; await navigate('/platform/dashboard', 'main [role=alert]'); await assert('!document.querySelector(".kpi-card")', 'platform_revocation_clears_totals'); await capture(stage);
  if (requests.some(r => r.method !== 'GET')) throw Error('Read-only browser QA unexpectedly mutated a resource'); checks.no_mutations = true;
  if (errors.length) throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(audit, 'dashboard-d5-d7-browser.json'), JSON.stringify({ passed: true, fixture_only: true, working_database_writes: 0, checks, screenshots, timings_ms: timings, requests }, null, 2));
  console.log(JSON.stringify({ passed: true, checks: Object.keys(checks).length, screenshots: screenshots.length }));
} catch (failure) { try { await capture('failure'); } catch {} fs.writeFileSync(path.join(privateDir, 'dashboard-d5-d7-browser-error.json'), JSON.stringify({ stage, message: failure.message, errors, recent_requests: requests.slice(-12) }, null, 2)); console.error(stage + ': ' + failure.message); process.exitCode = 1; }
finally { if (socket?.readyState === WebSocket.OPEN) { try { await send('Browser.close'); } catch {} socket.close(); } if (chrome.exitCode === null) chrome.kill(); server.closeAllConnections(); server.close(); }
