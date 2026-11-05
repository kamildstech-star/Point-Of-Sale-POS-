// Actual Chrome UI checks against synthetic HTTP fixtures; no working DB access.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { dashboardFixture, fixtureTenant } from '../src/test/fixtures/businessDashboard.js';

const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend);
const output = path.join(root, 'docs', 'design', 'd3'), audit = path.join(root, 'docs', 'audit');
const privateDir = path.join(audit, 'private');
fs.mkdirSync(output, { recursive: true }); fs.mkdirSync(privateDir, { recursive: true });
let tenant = fixtureTenant(), mode = 'ready', gate, releaseGate;
const requests = [], errors = [];
const paged = data => ({ data, total: data.length, current_page: 1, last_page: 1 });
function staff() { return { id: 83, name: 'Synthetic Owner', role: { name: tenant.dashboard.profile === 'admin' ? 'Admin' : 'Owner' }, permissions: tenant.permissions,
  active_tenant: { id: tenant.id, name: tenant.name, currency_code: tenant.currency, timezone: tenant.timezone } }; }
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://fixture.test');
    if (url.pathname.startsWith('/api/')) {
      requests.push(url.pathname + url.search); res.setHeader('Content-Type', 'application/json');
      let result;
      if (url.pathname === '/api/v1/auth/me') result = { data: staff() };
      else if (url.pathname === '/api/v1/me/context') result = { data: { active_tenant: tenant } };
      else if (url.pathname.startsWith('/api/v1/dashboard/')) {
        if (gate) await gate;
        if ((mode === 'partial-error' && url.pathname.endsWith('/sales-trend')) || (mode === 'forbidden' && url.pathname.endsWith('/alerts'))) {
          res.statusCode = mode === 'forbidden' ? 403 : 503; res.end(JSON.stringify({ message: mode === 'forbidden' ? 'Access changed.' : 'Synthetic trend service unavailable.' })); return;
        }
        result = dashboardFixture(url.pathname.slice('/api/v1/'.length) + url.search, tenant);
        if (mode === 'empty') {
          if (url.pathname.endsWith('/summary')) { result.data.sales.invoice_count = 0; result.data.sales.totals.net_sales_excluding_tax = '0.0000'; result.data.inventory.low_stock_locations = null; result.data.inventory.low_stock_threshold = null; result.data.approvals = { counts: {}, items: [] }; }
          else if (url.pathname.endsWith('/sales-trend')) result.data.series = [];
          else if (result.data.items) result.data.items = [];
        }
      } else if (url.pathname === '/api/v1/catalog/locations') result = paged([{ id: 3, name: 'Main stock' }]);
      else if (url.pathname === '/api/v1/inventory/balances') result = paged([{ id: 4, sku: 'SYNTH-SHIRT-M', location_name: 'Main stock', on_hand: '2.0000', reserved: '0.0000', version: 1 }]);
      else if (url.pathname === '/api/v1/inventory/adjustments/12') result = { data: { id: 12, status: 'draft', reason: 'Synthetic count review', items: [] } };
      else if (url.pathname === '/api/v1/purchasing/orders/17') result = { data: { id: 17, status: 'draft', items: [] } };
      else if (url.pathname === '/api/v1/reports/summary') result = { data: { period: { from: url.searchParams.get('from'), to: url.searchParams.get('to'), timezone: tenant.timezone }, totals: { net_sales_excluding_tax: url.searchParams.get('branch_id') === '2' ? '-12.3500' : '90.0000' }, methods: {}, current_balances: { due: '0.00', refund_due: '0.00' }, reconciled: true, basis: 'Synthetic posted-value report fixture.' } };
      else result = paged([]);
      res.end(JSON.stringify(result)); return;
    }
    const dist = path.join(frontend, 'dist'), candidate = path.resolve(dist, decodeURIComponent(url.pathname).replace(/^\/+/, ''));
    if (candidate !== dist && !candidate.startsWith(dist + path.sep)) { res.statusCode = 403; res.end(); return; }
    const file = fs.existsSync(candidate) && fs.statSync(candidate).isFile() ? candidate : path.join(dist, 'index.html');
    res.setHeader('Content-Type', ({ '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.woff': 'font/woff', '.woff2': 'font/woff2' })[path.extname(file)] || 'application/octet-stream');
    res.end(fs.readFileSync(file));
  } catch (error) { errors.push(error.message); res.statusCode = 500; res.end('{}'); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const profile = fs.mkdtempSync(path.join(privateDir, 'dashboard-d3-chrome-'));
const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let socket, sequence = 0;
const pending = new Map(), checks = {}, screenshots = [];
let stage = 'startup';
function checkpoint(name) { stage = name; console.log(name); }
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function contrast(label) {
  const result = await evaluate(`(()=>{
    const rgb=c=>c.match(/[\\d.]+/g).slice(0,3).map(Number),lum=c=>rgb(c).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);
    const failures=[...document.querySelectorAll('.business-dashboard h1,.business-dashboard h2,.business-dashboard h3,.business-dashboard p,.business-dashboard a,.business-dashboard label,.business-dashboard th,.business-dashboard td,.business-dashboard dt,.business-dashboard dd,.business-dashboard summary,.business-dashboard button')].filter(e=>e.getBoundingClientRect().width&&!e.disabled).map(e=>{const c=getComputedStyle(e);let p=e,bg=c.backgroundColor;while(bg==='rgba(0, 0, 0, 0)'&&p.parentElement){p=p.parentElement;bg=getComputedStyle(p).backgroundColor;}const a=lum(c.color),b=lum(bg),ratio=(Math.max(a,b)+.05)/(Math.min(a,b)+.05),large=parseFloat(c.fontSize)>=24||(parseFloat(c.fontSize)>=18.66&&Number(c.fontWeight)>=700);return {text:e.textContent.slice(0,60),ratio,required:large?3:4.5};}).filter(e=>e.ratio<e.required);
    return {failures};
  })()`);
  if (result.failures.length) throw Error(`${label}: ${JSON.stringify(result.failures)}`);
  checks[label] = true;
}
async function send(method, params = {}) {
  const id = ++sequence;
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => { pending.delete(id); reject(Error(`CDP timeout: ${method}`)); }, 30000);
    pending.set(id, { resolve: value => { clearTimeout(timer); resolve(value); }, reject }); socket.send(JSON.stringify({ id, method, params }));
  });
}
async function evaluate(expression) { const response = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (response.exceptionDetails) throw Error(response.exceptionDetails.exception?.description || response.exceptionDetails.text); return response.result.value; }
async function until(expression) { const start = Date.now(); while (Date.now() - start < 20000) { if (await evaluate(expression)) return; await delay(100); } throw Error(`Timed out: ${expression}`); }
async function navigate(route = '/', selector = '.business-dashboard') { await send('Page.navigate', { url: origin + route }); await until(`document.readyState==='complete' && !!document.querySelector(${JSON.stringify(selector)})`); }
async function capture(name) { const result = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); fs.writeFileSync(path.join(output, `${name}.png`), Buffer.from(result.data, 'base64')); screenshots.push(`${name}.png`); }
async function clickButton(label) {
  const box = await evaluate(`(()=>{const b=[...document.querySelectorAll('button')].find(e=>(e.getAttribute('aria-label')===${JSON.stringify(label)}||e.textContent.trim()===${JSON.stringify(label)})&&!e.disabled);if(!b)throw Error('Missing button');b.scrollIntoView({block:'center'});const r=b.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2};})()`);
  await send('Input.dispatchMouseEvent', { type: 'mouseMoved', ...box }); await send('Input.dispatchMouseEvent', { type: 'mousePressed', button: 'left', clickCount: 1, ...box }); await send('Input.dispatchMouseEvent', { type: 'mouseReleased', button: 'left', clickCount: 1, ...box });
}
async function assert(expression, label) { if (!await evaluate(expression)) throw Error(label); checks[label] = true; }
try {
  const endpoint = await new Promise((resolve, reject) => { let output = ''; const timer = setTimeout(() => reject(Error('Chrome startup timeout')), 20000); chrome.on('error', reject); chrome.stderr.on('data', chunk => { output += chunk; const match = output.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timer); resolve(match[1]); } }); });
  const targets = await (await fetch(endpoint.replace('ws:', 'http:').replace(/\/devtools\/browser\/.*/, '/json/list'))).json();
  socket = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
  socket.addEventListener('message', event => { const message = JSON.parse(event.data); if (message.id) { const task = pending.get(message.id); if (task) { pending.delete(message.id); message.error ? task.reject(Error(message.error.message)) : task.resolve(message.result); } } else if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text); });
  await send('Page.enable'); await send('Runtime.enable'); await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
  console.log('Checking D3 owner/admin dashboards in actual Chrome with synthetic API data.');
  // Preserve the previous welcome view as a reviewable baseline.
  tenant.dashboard.profile = null; await navigate('/', '.welcome-banner'); await capture('before-welcome-light'); tenant.dashboard.profile = 'owner';
  for (const theme of ['light', 'dark']) {
    checkpoint(`Owner ${theme}`);
    await navigate();
    if (await evaluate(`document.documentElement.dataset.theme!==${JSON.stringify(theme)}`)) await clickButton(theme === 'dark' ? 'Switch to dark theme' : 'Switch to light theme');
    await until(`document.documentElement.dataset.theme===${JSON.stringify(theme)}`);
    for (const width of [360, 768, 1280, 1440]) {
      await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 768 });
      await evaluate('window.scrollTo(0,0)'); await delay(150);
      await assert('document.documentElement.scrollWidth<=innerWidth+1', `${theme}_${width}_no_page_overflow`);
      await assert(`document.querySelectorAll('.business-dashboard .kpi-card').length===8 && document.querySelector('.business-dashboard').textContent.includes('77.65')`, `${theme}_${width}_verified_cards`);
      await capture(`owner-${theme}-${width}`);
    }
    await assert(`!!document.querySelector('figure svg[role="img"]') && !!document.querySelector('figure details table')`, `${theme}_accessible_chart_table`);
    await assert(`document.querySelector('.kpi-card:nth-child(3)').textContent.includes('Unavailable')`, `${theme}_profit_unavailable`);
    await contrast(`${theme}_owner_text_contrast`);
  }
  await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
  checkpoint('Branch and document drill-downs');
  await evaluate(`(()=>{const s=document.querySelector('[aria-label="Branch"]');s.value='2';s.dispatchEvent(new Event('change',{bubbles:true}));})()`);
  await until(`document.querySelector('[aria-label="Branch"]')?.value==='2' && document.querySelector('.kpi-card__value')?.textContent.includes('12.35')`);
  checks.branch_refetch_and_negative_credit = true;
  const reportLink = await evaluate(`document.querySelector('.kpi-card__link').getAttribute('href')`);
  await navigate(reportLink, '.dashboard-drilldown'); await until(`document.querySelector('main').textContent.includes('-12.3500')`); checks.branch_report_drilldown = true;
  await navigate();
  const stockLink = await evaluate(`document.querySelector('.dashboard-alert a').getAttribute('href')`);
  await navigate(stockLink, '.dashboard-drilldown'); await until(`document.querySelector('tbody').textContent.includes('SYNTH-SHIRT-M')`); checks.stock_alert_drilldown = requests.some(url => url.includes('inventory/balances?location_id=3&variant_id=4&page=1'));
  await navigate('/inventory?tab=adjustments&document_id=12', '.dashboard-drilldown'); await until(`document.querySelector('main').textContent.includes('adjustments #12')`); checks.stock_approval_drilldown = true;
  await navigate('/purchasing?tab=orders&document_id=17', '.dashboard-drilldown'); await until(`document.querySelector('main').textContent.includes('orders #17')`); checks.purchase_approval_drilldown = true;
  tenant.dashboard.profile = 'admin';
  for (const theme of ['light', 'dark']) {
    checkpoint(`Admin ${theme}`);
    await navigate('/', '.business-dashboard--admin');
    if (await evaluate(`document.documentElement.dataset.theme!==${JSON.stringify(theme)}`)) await clickButton(theme === 'dark' ? 'Switch to dark theme' : 'Switch to light theme');
    await until(`document.documentElement.dataset.theme===${JSON.stringify(theme)}`);
    for (const width of [360, 768, 1280, 1440]) {
      await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 768 });
      await evaluate('window.scrollTo(0,0)'); await delay(100);
      await assert('document.documentElement.scrollWidth<=innerWidth+1', `${theme}_admin_${width}_no_page_overflow`);
      await capture(`admin-${theme}-${width}`);
    }
    await assert(`document.querySelector('main').textContent.includes('Open shifts') && document.querySelector('main').textContent.includes('Open purchase orders') && !!document.querySelector('main a[href="/team"]')`, `${theme}_admin_operations`);
    await contrast(`${theme}_admin_text_contrast`);
  }
  checkpoint('Partial failure and retry');
  tenant.dashboard.profile = 'owner'; mode = 'partial-error'; await navigate(); await until(`!!document.querySelector('.chart-card [role="alert"]')`); await capture('owner-partial-error'); checks.inline_retry = true;
  mode = 'ready'; await clickButton('Try again'); await until(`!!document.querySelector('figure') && !document.querySelector('main [role="alert"]')`);
  checkpoint('Revoked permission');
  mode = 'forbidden'; await navigate('/', 'main [role="alert"]'); await assert(`!document.querySelector('.kpi-card')`, 'revoked_access_hides_all_figures');
  checkpoint('Empty and zero states');
  mode = 'empty'; await navigate(); await assert(`document.querySelector('.kpi-card__value').textContent.includes('0.00') && document.querySelector('main').textContent.includes('No data for this period')`, 'empty_and_zero_states'); await capture('owner-empty');
  checkpoint('Loading and business switch');
  mode = 'ready'; gate = new Promise(resolve => { releaseGate = resolve; }); await navigate('/', 'main'); await until(`!!document.querySelector('.loading-state')`); await capture('owner-loading'); checks.named_loading_state = true; releaseGate(); gate = null; await until(`!!document.querySelector('.business-dashboard')`);
  tenant = fixtureTenant({ id: 2, name: 'Second Synthetic Retail', access_fingerprint: 'test-policy-2' }); await navigate(); await assert(`document.querySelector('main header').textContent.includes('Second Synthetic Retail')`, 'business_context_replaced');
  if (errors.length || Object.values(checks).some(value => !value)) throw Error(`Browser errors: ${JSON.stringify(errors)}`);
  fs.writeFileSync(path.join(audit, 'dashboard-d3-browser.json'), JSON.stringify({ passed: true, fixture_only: true, working_database_writes: 0, checks, screenshots, requests }, null, 2));
  console.log(JSON.stringify({ passed: true, checks: Object.keys(checks).length, screenshots: screenshots.length }));
} catch (error) {
  try { await capture('failure'); } catch {}
  fs.writeFileSync(path.join(privateDir, 'dashboard-d3-browser-error.json'), JSON.stringify({ message: error.message, stage, errors, requests: requests.slice(-20) }, null, 2)); console.error(`${stage}: ${error.message}`); process.exitCode = 1;
} finally {
  releaseGate?.(); if (socket?.readyState === WebSocket.OPEN) { try { await send('Browser.close'); } catch {} socket.close(); }
  if (chrome.exitCode === null) chrome.kill(); server.closeAllConnections(); server.close();
}
