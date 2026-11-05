// D4 UI verification against synthetic HTTP fixtures. Never writes the POS DB.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { dashboardFixture, fixtureTenant } from '../src/test/fixtures/businessDashboard.js';

const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend), output = path.join(root, 'docs/design/refinement/ux3/staff'), audit = path.join(root, 'docs/audit'), privateDir = path.join(audit, 'private');
fs.mkdirSync(output, { recursive: true }); fs.mkdirSync(privateDir, { recursive: true });
let profileName = 'cashier', mode = 'ready', gate, releaseGate;
const requests = [], errors = [], observed = {}, checks = {}, screenshots = [];
const basePermissions = [...fixtureTenant().permissions, 'sales.return', 'payments.record', 'customers.view'];
function tenant() {
  const result = fixtureTenant({ name: 'Synthetic Staff Retail', permissions: basePermissions, branch_scope: 'assigned' });
  result.dashboard = { ...result.dashboard, profile: profileName, held_sales: true,
    allowed_widgets: profileName === 'cashier' ? ['cashier'] : ['sales', 'sales-trend', 'recent-sales', 'inventory', 'alerts', 'approvals', 'staff-activity'] };
  if (mode === 'no-branches') result.allowed_branches = [];
  return result;
}
const paged = rows => ({ data: rows, total: rows.length, current_page: 1, last_page: 1 });
let carts = [{ id: 8, branch_id: 1, source_session_id: 5, label: 'Fitting room', version: 1, status: 'held', item_count: 1, updated_at: '2026-11-03T10:00:00Z', cart: { customer_id: null, price_list_id: null, discount_amount: '0', items: [{ variant_id: 9, name: 'Test shirt', sku: 'D4-SHIRT', qty: '2', active: true }] } }];
const session = { id: 5, register_id: 2, register_name: 'Front counter', branch_id: 1, branch_name: 'Main Store', opened_at: '2026-11-03T08:00:00Z' };
const receipt = { id: 31, invoice_no: 'SYNTH-OWN-31', sold_at: '2026-11-03T10:00:00Z', branch_id: 1, cash_session_id: 5, grand_total: '24.69', currency_code: 'PKR', payment_status: 'paid', sale_status: 'completed' };
const pagination = { current_page: 1, last_page: 1, per_page: 5, total: 1 };
function dashboard(pathname, search) {
  const resource = pathname.split('/').at(-1), subject = tenant();
  const result = dashboardFixture(`dashboard/${['cashier-summary', 'staff-activity', 'recent-sales'].includes(resource) ? 'summary' : resource}${search}`, subject);
  if (resource === 'cashier-summary') {
    result.meta.sales_scope = 'own'; result.meta.cash_session_id = new URLSearchParams(search).get('cash_session_id');
    result.data = { scope: 'own', open_sessions: mode === 'no-shift' ? 0 : 1, sessions: mode === 'no-shift' ? [] : [session], selected_session: mode === 'no-shift' ? null : session,
      shift_totals: mode === 'no-shift' ? null : { invoice_count: 1, net_invoiced_including_tax: '12.3400' }, receipts: { items: [receipt], pagination }, held_carts: { count: carts.filter(cart => cart.status === 'held').length } };
  }
  if (resource === 'staff-activity') result.data = { items: [{ membership_id: 7, name: 'Branch cashier', invoice_count: 1, net_sales_excluding_tax: '77.6500', open_sessions: 1 }], pagination };
  if (resource === 'recent-sales') result.data = { items: [receipt], pagination };
  if (resource === 'summary') result.data.sales.average_invoice_value_including_tax = '25.88';
  return result;
}
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://fixture.test');
    if (url.pathname.startsWith('/api/')) {
      let payload = ''; for await (const chunk of req) payload += chunk;
      const body = payload ? JSON.parse(payload) : {};
      requests.push({ method: req.method, path: url.pathname + url.search }); res.setHeader('Content-Type', 'application/json');
      let result;
      if (url.pathname === '/api/v1/auth/me') result = { data: { id: 84, name: 'Synthetic Staff', role: { name: profileName === 'cashier' ? 'Cashier' : 'Manager' }, permissions: basePermissions, active_tenant: { id: 1, name: tenant().name, currency_code: 'PKR', timezone: 'Asia/Karachi' } } };
      else if (url.pathname === '/api/v1/me/context') result = { data: { active_tenant: tenant() } };
      else if (url.pathname.startsWith('/api/v1/dashboard/')) {
        if (gate) await gate;
        if (mode === 'forbidden') { res.statusCode = 403; res.end(JSON.stringify({ message: 'Access changed.' })); return; }
        if (mode === 'partial-error' && url.pathname.endsWith('/staff-activity')) { res.statusCode = 503; res.end(JSON.stringify({ message: 'Synthetic staff service unavailable.' })); return; }
        result = dashboard(url.pathname, url.search);
      } else if (url.pathname === '/api/v1/cash/sessions') result = paged(mode === 'no-shift' ? [] : [session]);
      else if (url.pathname === '/api/v1/operations/registers') result = paged([{ id: 2, name: 'Front counter', branch_id: 1 }]);
      else if (url.pathname === '/api/v1/operations/payment-methods') result = paged([{ id: 1, name: 'Cash', method_type: 'cash', active: true }]);
      else if (url.pathname === '/api/v1/pos/products') result = paged([{ id: 9, sku: 'D4-SHIRT', name: 'Test shirt', default_price: '12.3456' }]);
      else if (url.pathname === '/api/v1/pos/held-carts') {
        if (req.method === 'POST') { observed.hold = body; const row = { ...carts[0], id: 10, label: body.label, version: 1, cart: { ...body, items: body.items.map(item => ({ ...item, name: 'Test shirt', sku: 'D4-SHIRT' })) } }; carts.push(row); result = { data: row }; }
        else result = paged(carts.filter(cart => cart.status === 'held'));
      } else if (/\/pos\/held-carts\/\d+$/.test(url.pathname)) result = { data: carts.find(cart => cart.id === Number(url.pathname.split('/').at(-1))) };
      else if (url.pathname.endsWith('/discard')) { observed.discard = body; const id = Number(url.pathname.split('/').at(-2)); carts.find(cart => cart.id === id).status = 'discarded'; result = { data: { id, status: 'discarded' } }; }
      else if (url.pathname === '/api/v1/pos/quotes') { observed.quote = body; result = { data: { id: 51, expires_at: new Date(Date.now() + 600000).toISOString(), quote: { subtotal: '24.69', discount_total: '0.00', tax_total: '0.00', grand_total: '24.69', currency_code: 'PKR', currency_scale: 2, allow_credit: true } } }; }
      else if (url.pathname === '/api/v1/pos/checkout') { observed.checkout = body; if (observed.quote?.held_cart_id) carts.find(cart => cart.id === observed.quote.held_cart_id).status = 'sold'; result = { data: { id: 81, invoice_no: 'SYNTH-81' } }; }
      else if (url.pathname === '/api/v1/sales/31') result = { data: { ...receipt, branch_snapshot_json: JSON.stringify({ business: 'Synthetic Staff Retail', name: 'Main Store', timezone: 'Asia/Karachi', receipt: {} }), customer_snapshot_json: null, items: [], payments: [], subtotal: '24.69', discount_total: '0.00', tax_total: '0.00', paid_total: '24.69', balance_due: '0.00', total_refunded: '0.00' } };
      else result = paged([]);
      res.end(JSON.stringify(result)); return;
    }
    const dist = path.join(frontend, 'dist'), candidate = path.resolve(dist, decodeURIComponent(url.pathname).replace(/^\/+/, ''));
    if (candidate !== dist && !candidate.startsWith(dist + path.sep)) { res.statusCode = 403; res.end(); return; }
    const file = fs.existsSync(candidate) && fs.statSync(candidate).isFile() ? candidate : path.join(dist, 'index.html');
    res.setHeader('Content-Type', ({ '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.woff': 'font/woff', '.woff2': 'font/woff2' })[path.extname(file)] || 'application/octet-stream'); res.end(fs.readFileSync(file));
  } catch (error) { errors.push(error.message); res.statusCode = 500; res.end('{}'); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve)); const origin = `http://127.0.0.1:${server.address().port}`;
const chromeProfile = fs.mkdtempSync(path.join(privateDir, 'refinement-ux3-staff-chrome-'));
const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--remote-debugging-port=0', `--user-data-dir=${chromeProfile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let socket, sequence = 0, stage = 'startup'; const pending = new Map(); const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
function checkpoint(value) { stage = value; console.log(value); }
async function send(method, params = {}) { const id = ++sequence; return new Promise((resolve, reject) => { const timer = setTimeout(() => { pending.delete(id); reject(Error(`CDP timeout: ${method}`)); }, 30000); pending.set(id, { resolve: result => { clearTimeout(timer); resolve(result); }, reject }); socket.send(JSON.stringify({ id, method, params })); }); }
async function evaluate(expression) { const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (result.exceptionDetails) throw Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text); return result.result.value; }
async function until(expression) { const start = Date.now(); while (Date.now() - start < 20000) { if (await evaluate(expression)) return; await delay(100); } throw Error(`Timed out: ${expression}`); }
async function navigate(route = '/', selector = '.staff-dashboard') { await evaluate('window.__d4NavigationPending=true'); await send('Page.navigate', { url: origin + route }); await until(`!window.__d4NavigationPending && document.readyState==='complete' && !!document.querySelector(${JSON.stringify(selector)})`); }
async function assert(expression, label) { if (!await evaluate(expression)) throw Error(label); checks[label] = true; }
async function capture(name) { const result = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); fs.writeFileSync(path.join(output, `${name}.png`), Buffer.from(result.data, 'base64')); screenshots.push(`${name}.png`); }
async function click(label) { const box = await evaluate(`(()=>{const b=[...document.querySelectorAll('button')].find(e=>(e.getAttribute('aria-label')===${JSON.stringify(label)}||e.textContent.trim()===${JSON.stringify(label)})&&!e.disabled&&!e.closest('[inert]'));if(!b)throw Error('Missing button: '+${JSON.stringify(label)});b.scrollIntoView({block:'center'});const r=b.getBoundingClientRect(),x=r.x+r.width/2,y=r.y+r.height/2;if(!b.contains(document.elementFromPoint(x,y)))throw Error('Obscured button');return{x,y};})()`); await send('Input.dispatchMouseEvent', { type: 'mouseMoved', ...box }); await send('Input.dispatchMouseEvent', { type: 'mousePressed', button: 'left', clickCount: 1, ...box }); await send('Input.dispatchMouseEvent', { type: 'mouseReleased', button: 'left', clickCount: 1, ...box }); }
async function contrast(label) { const failures = await evaluate(`(()=>{const rgb=c=>c.match(/[\\d.]+/g).slice(0,3).map(Number),lum=c=>rgb(c).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);return [...document.querySelectorAll('main h1,main h2,main h3,main p,main a,main label,main th,main td,main button')].filter(e=>e.getBoundingClientRect().width&&!e.disabled).map(e=>{const c=getComputedStyle(e);let p=e,bg=c.backgroundColor;while(bg==='rgba(0, 0, 0, 0)'&&p.parentElement){p=p.parentElement;bg=getComputedStyle(p).backgroundColor;}const a=lum(c.color),b=lum(bg),ratio=(Math.max(a,b)+.05)/(Math.min(a,b)+.05),large=parseFloat(c.fontSize)>=24||(parseFloat(c.fontSize)>=18.66&&Number(c.fontWeight)>=700);return {text:e.textContent.slice(0,60),ratio,minimum:large?3:4.5};}).filter(e=>e.ratio<e.minimum);})()`); if (failures.length) throw Error(`${label}: ${JSON.stringify(failures)}`); checks[label] = true; }
try {
  const endpoint = await new Promise((resolve, reject) => { let buffer = ''; const timer = setTimeout(() => reject(Error('Chrome startup timeout')), 20000); chrome.on('error', reject); chrome.stderr.on('data', chunk => { buffer += chunk; const match = buffer.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timer); resolve(match[1]); } }); });
  const targets = await (await fetch(endpoint.replace('ws:', 'http:').replace(/\/devtools\/browser\/.*/, '/json/list'))).json(); socket = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
  socket.addEventListener('message', event => { const message = JSON.parse(event.data); if (message.id) { const task = pending.get(message.id); if (task) { pending.delete(message.id); message.error ? task.reject(Error(message.error.message)) : task.resolve(message.result); } } else if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text); });
  await send('Page.enable'); await send('Runtime.enable');
  for (const role of ['cashier', 'manager']) {
    profileName = role; checkpoint(`Checking ${role}`);
    for (const theme of ['light', 'dark']) {
      await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false }); await navigate();
      if (await evaluate(`document.documentElement.dataset.theme!==${JSON.stringify(theme)}`)) await click(theme === 'dark' ? 'Switch to dark theme' : 'Switch to light theme');
      await until(`document.documentElement.dataset.theme===${JSON.stringify(theme)}`);
      for (const width of [360, 768, 1280, 1440]) { await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 768 }); await evaluate('window.scrollTo(0,0)'); await delay(150); await assert('document.documentElement.scrollWidth<=innerWidth+1', `${role}_${theme}_${width}_fits`); await capture(`${role}-${theme}-${width}`); }
      await contrast(`${role}_${theme}_text_contrast`);
      if (role === 'cashier') await assert(`(()=>{const a=[...document.querySelectorAll('main a')].find(e=>e.textContent.trim()==='New Sale'),r=a.getBoundingClientRect();return r.height>=44&&r.width>=44&&a.getAttribute('href').includes('own=1')&&!document.querySelector('main').textContent.match(/gross profit|net profit/i);})()`, `${theme}_cashier_primary_action_and_privacy`);
      else await assert(`document.querySelector('[aria-label="Branch"]').value==='1'&&![...document.querySelector('[aria-label="Branch"]').options].some(e=>e.textContent==='All permitted branches')&&!!document.querySelector('a[href*="cashier_membership_id=7"]')`, `${theme}_manager_branch_and_staff_links`);
    }
  }
  checkpoint('Cashier receipt, Hold/Recall and fresh checkout'); profileName = 'cashier'; await navigate();
  const cashierRequests = requests.filter(row => row.path.startsWith('/api/v1/dashboard/cashier-summary')); if (!cashierRequests.length) throw Error('Cashier endpoint was not requested');
  checks.personal_endpoint_used = true;
  const receiptLink = await evaluate(`document.querySelector('main a[href="/sales/31?own=1"]').getAttribute('href')`); await navigate(receiptLink, '.receipt'); checks.personal_receipt_drilldown = true;
  await navigate(); const posLink = await evaluate(`[...document.querySelectorAll('main a')].find(e=>e.textContent.trim()==='New Sale').getAttribute('href')`); await navigate(posLink, '.pos-layout'); await until(`!!document.querySelector('.held-cart-controls') && !!document.querySelector('.product-choice')`);
  await click('Recall held cart'); await until(`!!document.querySelector('[role="dialog"] tbody tr')`); await capture('held-carts-desktop');
  await assert(`document.querySelector('[role="dialog"]').contains(document.activeElement)&&!!document.querySelector('#workspace-content[inert]')||!!document.querySelector('[inert]')`, 'recall_dialog_focus_isolation');
  await click('Recall'); await until(`!document.querySelector('[role="dialog"]') && !!document.querySelector('[aria-label="Quantity for Test shirt · D4-SHIRT"]')`);
  await assert(`document.querySelector('[aria-label="Open register shift"]').value==='5'&&!document.querySelector('[aria-label="Server quote"]')`, 'recall_clears_quote_and_uses_own_shift');
  await click('Get server quote'); await until(`!!document.querySelector('[aria-label="Server quote"]')`); if (observed.quote.held_cart_id !== 8 || observed.quote.held_cart_version !== 1) throw Error('Quote was not bound to held cart version'); checks.fresh_quote_bound_to_held_cart = true;
  await click('Complete sale'); await until(`document.querySelector('main').textContent.includes('Sale completed: SYNTH-81')`); checks.recalled_cart_checkout = true;
  await until(`!!document.querySelector('.product-choice:not(:disabled)')`); await evaluate(`document.querySelector('.product-choice').click()`); await until(`!!document.querySelector('[aria-label="Quantity for Test shirt · D4-SHIRT"]')`);
  await click('Hold cart'); await until(`!!document.querySelector('[role="dialog"] [name="label"]')`); await evaluate(`document.querySelector('[role="dialog"] [name="label"]').focus()`); await send('Input.insertText', { text: 'Another fitting' });
  await capture('hold-cart-popup'); await click('Save held cart'); await until(`!document.querySelector('[role="dialog"]')&&!document.querySelector('[aria-label="Quantity for Test shirt · D4-SHIRT"]')`);
  if (!observed.hold?.idempotency_key || observed.hold.items[0].qty !== '1' || observed.hold.payments) throw Error('Incorrect hold request'); checks.hold_saves_cart_without_payments = true;
  checkpoint('No shift, no assignment, retry, access revocation and loading'); mode = 'no-shift'; await navigate();
  const shiftLink = await evaluate(`[...document.querySelectorAll('main a')].find(e=>e.textContent.trim()==='Open register shift').getAttribute('href')`); await navigate(shiftLink, '[role="dialog"]'); await assert(`document.querySelector('[role="dialog"]').textContent.includes('Open shift')`, 'no_shift_opens_actual_register_form'); await capture('open-shift-dialog');
  mode = 'no-branches'; profileName = 'manager'; const before = requests.length; await navigate(); await assert(`document.querySelector('main').textContent.includes('No permitted branches')&&!document.querySelector('.kpi-card')`, 'empty_assignment_hides_branch_figures'); if (requests.slice(before).some(row => row.path.startsWith('/api/v1/dashboard/'))) throw Error('Unassigned manager requested branch data');
  mode = 'partial-error'; await navigate(); await until(`!!document.querySelector('main [role="alert"]')`); await capture('manager-partial-error'); mode = 'ready'; await click('Try again'); await until(`!!document.querySelector('.staff-dashboard')&&!document.querySelector('main [role="alert"]')`); checks.optional_widget_retry = true;
  mode = 'forbidden'; await navigate('/', 'main [role="alert"]'); await assert(`!document.querySelector('.kpi-card')`, 'revoked_access_clears_figures');
  mode = 'ready'; gate = new Promise(resolve => { releaseGate = resolve; }); await navigate('/', 'main'); await until(`!!document.querySelector('.loading-state')`); await capture('staff-loading'); checks.named_loading_state = true; releaseGate(); gate = null; await until(`!!document.querySelector('.staff-dashboard')`);
  if (errors.length) throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(audit, 'refinement-ux3-staff-browser.json'), JSON.stringify({ passed: true, fixture_only: true, working_database_writes: 0, checks, screenshots, requests }, null, 2)); console.log(JSON.stringify({ passed: true, checks: Object.keys(checks).length, screenshots: screenshots.length }));
} catch (error) { try { await capture('failure'); } catch {} fs.writeFileSync(path.join(privateDir, 'refinement-ux3-staff-browser-error.json'), JSON.stringify({ stage, message: error.message, errors, recent_requests: requests.slice(-15) }, null, 2)); console.error(`${stage}: ${error.message}`); process.exitCode = 1; }
finally { releaseGate?.(); if (socket?.readyState === WebSocket.OPEN) { try { await send('Browser.close'); } catch {} socket.close(); } if (chrome.exitCode === null) chrome.kill(); server.closeAllConnections(); server.close(); }



