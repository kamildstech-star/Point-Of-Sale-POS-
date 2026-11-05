// Visual/density checks use synthetic HTTP responses only; never the POS database.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { dashboardFixture, fixtureTenant } from '../src/test/fixtures/businessDashboard.js';

const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend);
const phase = 'after';
const output = path.join(root, 'docs/design/refinement/ux1', phase), audit = path.join(root, 'docs/audit'), privateDir = path.join(audit, 'private');
fs.mkdirSync(output, { recursive: true }); fs.mkdirSync(privateDir, { recursive: true });
const tenant = fixtureTenant({ name: 'Synthetic Retail', permissions: [...fixtureTenant().permissions, 'customers.view', 'payments.record', 'sales.discount'] });
tenant.dashboard.held_sales = true;
const paged = data => ({ data, total: data.length, current_page: 1, last_page: 1 });
const products = Array.from({ length: 20 }, (_, i) => ({ id: i + 1, name: i ? `Cotton uniform shirt ${i + 1}` : 'Professional school uniform — breathable cotton, extra long product description for content wrapping', product_type: 'simple', track_inventory: true, active: true }));
const unit = { id: 1, name: 'Each', code: 'EA', quantity_precision: 0 };
function staffFixture() { return { id: 83, name: 'Synthetic Owner', role: { name: 'Owner' }, permissions: tenant.permissions, active_tenant: { id: tenant.id, name: tenant.name, currency_code: tenant.currency, timezone: tenant.timezone } }; }
let mode = 'ready';
const requests = [], errors = [], screenshots = [], checks = {}, measurements = {};
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://fixture.test');
    if (url.pathname.startsWith('/api/')) {
      let raw = ''; for await (const chunk of req) raw += chunk;
      requests.push({ method: req.method, path: url.pathname + url.search }); res.setHeader('Content-Type', 'application/json');
      let result;
      if (url.pathname === '/api/v1/auth/me') result = { data: staffFixture() };
      else if (url.pathname === '/api/v1/tenants') result = { data: [{ id: 1, name: 'Synthetic Retail' }, { id: 2, name: 'Synthetic Outlet' }] };
      else if (/^\/api\/v1\/tenants\/[12]\/activate$/.test(url.pathname)) {
        if (mode === 'switch-error') { res.statusCode = 403; result = { message: 'Synthetic business switch refused.' }; }
        else { tenant.id = Number(url.pathname.split('/').at(-2)); tenant.name = tenant.id === 1 ? 'Synthetic Retail' : 'Synthetic Outlet'; result = { data: staffFixture() }; }
      }
      else if (url.pathname === '/api/v1/me/context') result = { data: { active_tenant: tenant } };
      else if (url.pathname.startsWith('/api/v1/dashboard/')) result = dashboardFixture(url.pathname.slice('/api/v1/'.length) + url.search, tenant);
      else if (url.pathname === '/api/v1/products') {
        if (mode === 'error') { res.statusCode = 503; result = { message: 'Synthetic catalog temporarily unavailable.' }; }
        else result = paged(mode === 'empty' ? [] : products.filter(p => !url.searchParams.get('search') || p.name.includes(url.searchParams.get('search'))));
      } else if (url.pathname === '/api/v1/catalog/units') result = paged([unit]);
      else if (url.pathname === '/api/v1/cash/sessions') result = paged([{ id: 5, register_id: 2, branch_id: 1 }]);
      else if (url.pathname === '/api/v1/operations/payment-methods') result = paged([{ id: 1, name: 'Cash', method_type: 'cash', active: true }]);
      else if (url.pathname === '/api/v1/pos/products') result = paged([{ id: 9, name: 'Cotton shirt', sku: 'SYNTH-SHIRT', default_price: '1250.00' }]);
      else if (url.pathname === '/api/v1/pos/quotes') result = { data: { id: 51, expires_at: '2026-11-04T23:59:00Z', quote: { subtotal: '2500.00', discount_total: '0.00', tax_total: '375.00', grand_total: '2875.00', currency_code: 'PKR', currency_scale: 2, allow_credit: true } } };
      else if (url.pathname === '/api/v1/pos/held-carts') result = paged([]);
      else if (url.pathname === '/api/v1/reports/summary') result = { data: { period: { from: url.searchParams.get('from'), to: url.searchParams.get('to'), timezone: tenant.timezone }, totals: { net_sales_excluding_tax: '1234567890.1200', return_credits: '1750.00', net_tax: '375.00' }, methods: { Cash: { collections: '2875.00', refunds: '0.00' } }, current_balances: { due: '1250.00', refund_due: '0.00' }, reconciled: true, basis: 'Synthetic posted-value report.' } };
      else result = paged([]);
      res.end(JSON.stringify(result)); return;
    }
    const dist = path.join(frontend, 'dist'), candidate = path.resolve(dist, decodeURIComponent(url.pathname).replace(/^\/+/, ''));
    if (candidate !== dist && !candidate.startsWith(dist + path.sep)) { res.statusCode = 403; res.end(); return; }
    const file = fs.existsSync(candidate) && fs.statSync(candidate).isFile() ? candidate : path.join(dist, 'index.html');
    res.setHeader('Content-Type', ({ '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.woff': 'font/woff', '.woff2': 'font/woff2' })[path.extname(file)] || 'application/octet-stream'); res.end(fs.readFileSync(file));
  } catch (failure) { errors.push(failure.message); res.statusCode = 500; res.end('{}'); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const profile = fs.mkdtempSync(path.join(privateDir, `compact-${phase}-chrome-`));
const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let socket, sequence = 0, stage = 'startup'; const pending = new Map();
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function send(method, params = {}) { const id = ++sequence; return new Promise((resolve, reject) => { const timer = setTimeout(() => { pending.delete(id); reject(Error(`CDP timeout: ${method}`)); }, 30000); pending.set(id, { resolve: result => { clearTimeout(timer); resolve(result); }, reject }); socket.send(JSON.stringify({ id, method, params })); }); }
async function evaluate(expression) { const r = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (r.exceptionDetails) throw Error(r.exceptionDetails.exception?.description || r.exceptionDetails.text); return r.result.value; }
async function until(expression) { const start = Date.now(); while (Date.now() - start < 20000) { if (await evaluate(expression)) return; await delay(100); } throw Error(`Timed out: ${expression}`); }
async function navigate(route = '/', selector = '.business-dashboard') { await evaluate('window.__compactNavigationPending=true'); await send('Page.navigate', { url: origin + route }); await until(`!window.__compactNavigationPending&&document.readyState==='complete'&&!!document.querySelector(${JSON.stringify(selector)})`); await evaluate('document.fonts.ready'); }
async function viewport(width = 1440, zoom = 1) { await send('Emulation.setDeviceMetricsOverride', { width: Math.round(width / zoom), height: Math.round(1000 / zoom), deviceScaleFactor: zoom, mobile: false }); await evaluate('window.scrollTo(0,0)'); await delay(100); }
async function capture(name) { const r = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); fs.writeFileSync(path.join(output, `${name}.png`), Buffer.from(r.data, 'base64')); screenshots.push(`${name}.png`); }
async function click(label) { const b = await evaluate(`(()=>{const e=[...document.querySelectorAll('button')].find(b=>!b.disabled&&!b.closest('[inert],[aria-hidden="true"]')&&b.getBoundingClientRect().width&&(b.getAttribute('aria-label')===${JSON.stringify(label)}||b.textContent.trim()===${JSON.stringify(label)}));if(!e)throw Error('Missing button: '+${JSON.stringify(label)});e.scrollIntoView({block:'center'});const r=e.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2};})()`); await send('Input.dispatchMouseEvent', { type: 'mouseMoved', ...b }); await send('Input.dispatchMouseEvent', { type: 'mousePressed', button: 'left', clickCount: 1, ...b }); await send('Input.dispatchMouseEvent', { type: 'mouseReleased', button: 'left', clickCount: 1, ...b }); }
async function assert(expression, name) { if (!await evaluate(expression)) throw Error(name); checks[name] = true; }
async function measure(name) { measurements[name] = await evaluate(`(()=>{const box=s=>{const e=document.querySelector(s);if(!e)return null;const c=getComputedStyle(e),r=e.getBoundingClientRect();return {height:r.height,width:r.width,font:c.fontSize,padding:c.padding,gap:c.gap};};return {body:box('body'),title:box('main h1'),sidebar:box('.app-sidebar'),nav:box('.app-sidebar nav a'),navIcon:box('.app-sidebar nav a svg'),header:box('.app-topbar'),card:box('main .card'),control:box('main input:not([type=hidden])'),button:box('main button'),row:box('main tbody tr'),kpi:box('.kpi-card'),visibleRows:[...document.querySelectorAll('main tbody tr')].filter(e=>e.getBoundingClientRect().bottom<=innerHeight).length};})()`); }
async function contrast(name) { const failures = await evaluate(`(()=>{const rgb=c=>c.match(/[\\d.]+/g).slice(0,3).map(Number),lum=c=>rgb(c).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);return [...document.querySelectorAll('main h1,main h2,main h3,main p,main label,main th,main td,main button,main a,.app-sidebar nav a')].filter(e=>e.getBoundingClientRect().width&&!e.disabled).map(e=>{const c=getComputedStyle(e);let p=e,bg=c.backgroundColor;while(bg==='rgba(0, 0, 0, 0)'&&p.parentElement){p=p.parentElement;bg=getComputedStyle(p).backgroundColor;}const a=lum(c.color),b=lum(bg);return {text:e.textContent.slice(0,60),ratio:(Math.max(a,b)+.05)/(Math.min(a,b)+.05)};}).filter(e=>e.ratio<4.5);})()`); if (failures.length) throw Error(`${name}: ${JSON.stringify(failures)}`); checks[name] = true; }
async function escape() { await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' }); await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape' }); }
try {
  const endpoint = await new Promise((resolve, reject) => { let text = ''; const timer = setTimeout(() => reject(Error('Chrome startup timeout')), 20000); chrome.on('error', reject); chrome.stderr.on('data', chunk => { text += chunk; const match = text.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timer); resolve(match[1]); } }); });
  const targets = await (await fetch(endpoint.replace('ws:', 'http:').replace(/\/devtools\/browser\/.*/, '/json/list'))).json(); socket = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
  socket.addEventListener('message', event => { const m = JSON.parse(event.data); if (m.id) { const task = pending.get(m.id); if (task) { pending.delete(m.id); m.error ? task.reject(Error(m.error.message)) : task.resolve(m.result); } } else if (m.method === 'Runtime.exceptionThrown') errors.push(m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text); });
  await send('Page.enable'); await send('Runtime.enable'); await viewport();
  for (const theme of phase === 'before' ? ['light'] : ['light', 'dark']) {
    await navigate(); await evaluate(`localStorage.setItem('universal-pos:theme:user:83',${JSON.stringify(theme)})`);
    if (await evaluate(`document.documentElement.dataset.theme!==${JSON.stringify(theme)}`)) await click(theme === 'dark' ? 'Switch to dark theme' : 'Switch to light theme');
    await until(`document.documentElement.dataset.theme===${JSON.stringify(theme)}`);
    for (const [name, route, selector] of [['dashboard', '/', '.business-dashboard'], ['products', '/catalog', '.table-wrap tbody tr'], ['product-form', '/catalog/new', '[role="dialog"] [name="variant_sku"]'], ['reports', '/reports?from=2026-11-01&to=2026-11-04', 'main [role="status"]'], ['checkout', '/pos?cash_session_id=5', '.product-choice']]) {
      stage = `${theme} ${name}`; console.log(stage); await viewport(); await navigate(route, selector);
      if (name === 'checkout') { await evaluate('document.querySelector(".product-choice").click()'); await click('Get server quote'); await until(`!!document.querySelector('[aria-label="Server quote"]')`); }
      await measure(`${name}-${theme}`); await capture(`${name}-${theme}-1440`);
      if (phase === 'before') continue;
      await contrast(`${name}_${theme}_contrast`);
      if (name === 'dashboard') {
        await until(`(()=>{const s=document.querySelector('.dashboard-chart-grid .trend-chart svg');return Math.abs(s.getBoundingClientRect().width/s.viewBox.baseVal.width-1)<.02})()`);
        await assert(`(()=>{const s=document.querySelectorAll('.dashboard-chart-grid .trend-chart svg');return [...s].every(e=>parseFloat(getComputedStyle(e.querySelector('text')).fontSize)*e.getBoundingClientRect().width/e.viewBox.baseVal.width>=11.8)})()`, `${theme}_readable_chart_labels`);
        await assert(`document.querySelector('.dashboard-context').textContent.includes('All permitted branches')&&document.querySelector('.dashboard-context').textContent.includes('PKR')`, `${theme}_header_scope`);
        await assert(`document.querySelector('[aria-label="Key performance indicators"]').textContent.includes('Current receivables')&&!document.querySelector('[aria-label="Key performance indicators"]').textContent.includes('Gross profit')`, `${theme}_primary_verified_metrics`);
        await assert(`(document.querySelector('[aria-label="Action required"]').compareDocumentPosition(document.querySelector('.dashboard-actions-card'))&Node.DOCUMENT_POSITION_FOLLOWING)!==0`, `${theme}_exceptions_before_tools`);
        await assert(`(()=>{const c=document.querySelector('.dashboard-chart-grid').children;return c.length===2&&Math.abs(c[0].getBoundingClientRect().width/c[1].getBoundingClientRect().width-2)<.02})()`, `${theme}_primary_chart_hierarchy`);
        await assert(`['Workspace','Operations','Management'].every(label=>[...document.querySelectorAll('.sidebar-group-heading')].some(e=>e.textContent===label))&&document.querySelector('a.sidebar-link[aria-current="page"]').getAttribute('href')==='/'&&document.querySelector('aside .tenant-switcher')&&!document.querySelector('.app-topbar .tenant-switcher')`, `${theme}_grouped_sidebar_and_business_context`);
        await click('Choose business'); await until(`!!document.querySelector('[role="dialog"] select[name="business"]')`); await capture(`business-switcher-${theme}`); await escape();
        await click('Collapse navigation'); await assert(`document.querySelector('.app-sidebar').getBoundingClientRect().width===72&&document.querySelector('a[href="/catalog"]').getAttribute('aria-label')==='Products'`, `${theme}_collapsed_navigation`); await capture(`sidebar-collapsed-${theme}`);
        await click('Choose business'); await until(`!!document.querySelector('[role="dialog"] select[name="business"]')`); await escape(); await click('Expand navigation');
        await viewport(360); await click('Open navigation'); await until(`!!document.querySelector('.modal-navigation .sidebar-group-heading')`); await capture(`navigation-drawer-${theme}`);
        await click('Choose business'); await until(`document.querySelectorAll('[role="dialog"]').length===2&&!!document.querySelector('select[name="business"]')`); await escape(); await assert(`document.querySelectorAll('[role="dialog"]').length===1&&document.querySelector('.modal-navigation').contains(document.activeElement)`, `${theme}_nested_dialog_focus`);
        await escape(); await assert(`!document.querySelector('[role="dialog"]')&&!document.querySelector('[inert]')&&document.activeElement.getAttribute('aria-label')==='Open navigation'`, `${theme}_drawer_restores_document`); await viewport();
      }
      if (name === 'products') {
        await assert(`getComputedStyle(document.querySelector('main h1')).fontSize==='22px'&&document.querySelector('main input').getBoundingClientRect().height===36`, `${theme}_admin_sizes`);
        await assert(`document.querySelector('tbody tr:nth-child(2)').getBoundingClientRect().height>=40&&document.querySelector('tbody tr:nth-child(2)').getBoundingClientRect().height<=44`, `${theme}_table_density`);
        await assert(`document.querySelector('tbody td').scrollWidth<=document.querySelector('tbody td').clientWidth+1`, `${theme}_long_product_wraps`);
        await assert(`document.querySelector('.app-sidebar').getBoundingClientRect().width===232&&document.querySelector('.app-sidebar nav a svg').getBoundingClientRect().width===18`, `${theme}_navigation_sizes`);
        await evaluate(`document.querySelector('.record-actions button').focus()`);
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
        await assert(`!!document.activeElement.closest('.record-actions')&&getComputedStyle(document.activeElement).outlineStyle!=='none'`, `${theme}_keyboard_focus_visible`);
        const before = await evaluate(`document.querySelector('.app-sidebar').getBoundingClientRect().top`); await evaluate('window.scrollTo(0,800)'); await assert(`document.querySelector('.app-sidebar').getBoundingClientRect().top===${before}`, `${theme}_sidebar_stays_fixed`); await evaluate('window.scrollTo(0,0)');
      }
      if (name === 'product-form') {
        await assert(`document.querySelector('[role="dialog"]').contains(document.activeElement)&&document.querySelector('[role="dialog"]').dataset.density==='compact'`, `${theme}_admin_dialog_focus_and_density`);
        await click('Create product'); await until(`!!document.querySelector('[role="dialog"] .validation-summary')`); checks[`${theme}_validation_preserved`] = true;
        // Capture the responsive form without a transient validation toast over its header.
        await navigate(route, selector);
      }
      if (name === 'checkout') {
        await assert(`document.querySelector('.app-shell').dataset.density==='comfortable'&&[...document.querySelectorAll('main button,main input:not([type=hidden]),main select')].filter(e=>e.getBoundingClientRect().width).every(e=>e.getBoundingClientRect().height>=48)`, `${theme}_checkout_touch_targets`);
        await assert(`parseFloat(getComputedStyle(document.querySelector('.grand-total')).fontSize)>=20`, `${theme}_checkout_total_hierarchy`);
        await click('Hold cart'); await until(`!!document.querySelector('[role="dialog"] input[name="label"]')`);
        await assert(`document.querySelector('[role="dialog"]').dataset.density==='comfortable'&&document.querySelector('[role="dialog"] input').getBoundingClientRect().height>=48`, `${theme}_checkout_dialog_density`); await escape();
      }
      for (const width of [360, 768, 1280, 1920]) {
        await viewport(width); await assert('document.documentElement.scrollWidth<=innerWidth+1', `${name}_${theme}_${width}_reflows`);
        if (width === 360 || width === 1920) await capture(`${name}-${theme}-${width}`);
      }
      // Desktop zoom reflow: same physical viewport, effective CSS viewport/DPR at 125% and 200%.
      for (const zoom of [1.25, 2]) { await viewport(1440, zoom); await assert('document.documentElement.scrollWidth<=innerWidth+1', `${name}_${theme}_${zoom}_zoom_reflow`); if (zoom === 2) await capture(`${name}-${theme}-zoom-200`); }
    }
  }
  if (phase === 'after') {
    stage = 'business switching'; await viewport(); await navigate('/catalog', '.table-wrap tbody tr');
    await click('Choose business'); await until(`!!document.querySelector('select[name="business"]')`); mode = 'switch-error';
    await evaluate(`document.querySelector('select[name="business"]').value='2';document.querySelector('select[name="business"]').dispatchEvent(new Event('change',{bubbles:true}))`);
    await click('Switch business'); await until(`!!document.querySelector('[role="dialog"] [role="alert"]')`);
    await assert(`document.querySelector('select[name="business"]').value==='2'&&document.querySelector('aside .tenant-switcher').textContent.includes('Synthetic Retail')`, 'refused_switch_keeps_scope_and_selection'); await escape(); mode = 'ready';
    for (const id of [2,1]) {
      await click('Choose business'); await until(`!!document.querySelector('select[name="business"]')`);
      await evaluate(`document.querySelector('select[name="business"]').value='${id}';document.querySelector('select[name="business"]').dispatchEvent(new Event('change',{bubbles:true}))`);
      await click('Switch business'); await until(`!document.querySelector('[role="dialog"]')&&document.querySelector('aside .tenant-switcher').textContent.includes('${id===2?'Synthetic Outlet':'Synthetic Retail'}')`);
    }
    checks.business_switch_uses_real_activation_flow = true;
    stage = 'catalog empty and error states'; await viewport(); mode = 'empty'; await navigate('/catalog', '.empty-state'); checks.empty_state = true;
    mode = 'error'; await navigate('/catalog', 'main [role="alert"]'); checks.error_state = true; mode = 'ready'; await click('Try again'); await until(`!!document.querySelector('tbody tr')`); checks.retry = true;
    await send('Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 }); await viewport(768); await navigate('/pos?cash_session_id=5', '.product-choice');
    await assert(`[...document.querySelectorAll('main button,main input:not([type=hidden]),main select')].filter(e=>e.getBoundingClientRect().width).every(e=>e.getBoundingClientRect().height>=48)`, 'tablet_touch_targets');
    // Scanner keyboard wedge: exact SKU + Enter follows the existing scan handler.
    await evaluate(`document.querySelector('[aria-label="Scan barcode or search products"]').focus()`);
    await send('Input.insertText', { text: 'SYNTH-SHIRT' }); await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 }); await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
    await until(`!!document.querySelector('[aria-label="Quantity for Cotton shirt · SYNTH-SHIRT"]')`); checks.keyboard_scanner = true;
  }
  if (errors.length) throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(audit, `refinement-ux1-${phase}.json`), JSON.stringify({ passed: true, phase, fixture_only: true, working_database_writes: 0, checks, screenshots, measurements, requests }, null, 2)); console.log(JSON.stringify({ passed: true, phase, checks: Object.keys(checks).length, screenshots: screenshots.length }));
} catch (failure) { try { await capture('failure'); } catch {} fs.writeFileSync(path.join(privateDir, `refinement-ux1-${phase}-error.json`), JSON.stringify({ stage, message: failure.message, errors, recent_requests: requests.slice(-15) }, null, 2)); console.error(`${stage}: ${failure.message}`); process.exitCode = 1; }
finally { if (socket?.readyState === WebSocket.OPEN) { try { await send('Browser.close'); } catch {} socket.close(); } if (chrome.exitCode === null) chrome.kill(); server.closeAllConnections(); server.close(); }
