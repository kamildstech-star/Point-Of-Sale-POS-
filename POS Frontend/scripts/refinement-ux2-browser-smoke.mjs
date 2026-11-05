// Visual/density checks use synthetic HTTP responses only; never the POS database.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { dashboardFixture, fixtureTenant } from '../src/test/fixtures/businessDashboard.js';

const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend);
const phase = 'after';
const output = path.join(root, 'docs/design/refinement/ux2', phase), audit = path.join(root, 'docs/audit'), privateDir = path.join(audit, 'private');
fs.mkdirSync(output, { recursive: true }); fs.mkdirSync(privateDir, { recursive: true });
const tenant = fixtureTenant({ name: 'Synthetic Retail', permissions: [...fixtureTenant().permissions, 'customers.view', 'payments.record', 'sales.discount', 'purchases.manage', 'settings.manage', 'customers.manage'] });
tenant.dashboard.held_sales = true;
const paged = data => ({ data, total: data.length, current_page: 1, last_page: 1 });
const products = Array.from({ length: 45 }, (_, i) => ({ id: i + 1, name: i ? `Cotton uniform shirt ${i + 1}` : 'Professional school uniform — breathable cotton, extra long product description for content wrapping', product_type: 'simple', track_inventory: true, active: true }));
const unit = { id: 1, name: 'Each', code: 'EA', quantity_precision: 0 };
function staffFixture() { return { id: 83, name: 'Synthetic Owner', role: { name: 'Owner' }, permissions: tenant.permissions, active_tenant: { id: tenant.id, name: tenant.name, currency_code: tenant.currency, timezone: tenant.timezone } }; }
const variant = { id: 9, sku: 'SYNTH-SHIRT', base_unit_id: 1, default_cost: '1.2500', default_price: '12.3456', tracking_mode: 'none', active: true };
const pageOf = (rows, url) => { const page = Number(url.searchParams.get('page') || 1), per_page = 20, total = rows.length, data = rows.slice((page - 1) * per_page, page * per_page); return { data, total, per_page, current_page: page, last_page: Math.max(1, Math.ceil(total / per_page)), from: data.length ? (page - 1) * per_page + 1 : null, to: data.length ? (page - 1) * per_page + data.length : null }; };
const balances = products.map((p, i) => ({ id: p.id, sku: i ? 'SYNTH-SKU-' + p.id : 'SCHOOL-EXTRA-LONG-SKU-000000000000000000001', location_name: 'Main stock', on_hand: '12345678901234567890.1234', reserved: '0.0000', version: 1 }));
const orders = products.map(p => ({ id: p.id, po_number: 'PO-000' + p.id, supplier_name: 'Synthetic Supplier', location_name: 'Main stock', status: 'open', total: '12345678901234567890.1234' }));
let mode = 'ready';
const requests = [], errors = [], screenshots = [], checks = {}, measurements = {};
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://fixture.test');
    if (url.pathname.startsWith('/api/')) {
      let raw = ''; for await (const chunk of req) raw += chunk;
      requests.push({ method: req.method, path: url.pathname + url.search, ...(raw ? { body: JSON.parse(raw) } : {}) }); res.setHeader('Content-Type', 'application/json');
      let result;
      if (url.pathname === '/api/v1/auth/me') result = { data: staffFixture() };
      else if (url.pathname === '/api/v1/tenants') result = { data: [{ id: 1, name: 'Synthetic Retail' }, { id: 2, name: 'Synthetic Outlet' }] };
      else if (/^\/api\/v1\/tenants\/[12]\/activate$/.test(url.pathname)) {
        if (mode === 'switch-error') { res.statusCode = 403; result = { message: 'Synthetic business switch refused.' }; }
        else { tenant.id = Number(url.pathname.split('/').at(-2)); tenant.name = tenant.id === 1 ? 'Synthetic Retail' : 'Synthetic Outlet'; result = { data: staffFixture() }; }
      }
      else if (url.pathname === '/api/v1/me/context') result = { data: { active_tenant: tenant } };
      else if (url.pathname.startsWith('/api/v1/dashboard/')) result = dashboardFixture(url.pathname.slice('/api/v1/'.length) + url.search, tenant);
      else if (url.pathname === '/api/v1/products' && req.method === 'POST') {
        const body = JSON.parse(raw); if (mode === 'validation') { res.statusCode = 422; result = { message: 'Validation failed.', errors: { description: ['Synthetic description rejected.'] } }; }
        else { const row = { id: 99, ...body }; products.unshift(row); result = { data: row }; }
      } else if (/^\/api\/v1\/products\/\d+$/.test(url.pathname)) {
        const row = products.find(p => String(p.id) === url.pathname.split('/').at(-1));
        if (req.method === 'PATCH' || req.method === 'PUT') Object.assign(row, JSON.parse(raw));
        result = { data: { ...row, description: 'Original optional description', variants: [variant], images: [] } };
      } else if (url.pathname === '/api/v1/products') {
        if (mode === 'error') { res.statusCode = 503; result = { message: 'Synthetic catalog temporarily unavailable.' }; }
        else result = pageOf(mode === 'empty' ? [] : products.filter(p => !url.searchParams.get('search') || p.name.includes(url.searchParams.get('search'))), url);
      } else if (url.pathname === '/api/v1/catalog/units') result = paged([unit]);
      else if (url.pathname === '/api/v1/catalog/locations') result = paged([{ id: 3, name: 'Main stock', branch_id: 1 }]);
      else if (url.pathname === '/api/v1/operations/suppliers') result = paged([{ id: 5, name: 'Synthetic Supplier', active: true }]);
      else if (url.pathname === '/api/v1/operations/customers') result = pageOf(products.map(p => ({ id: p.id, name: p.name, phone: '+92 030012345678', email: 'synthetic@example.test', active: true })), url);
      else if (url.pathname === '/api/v1/inventory/balances') result = pageOf(url.searchParams.has('location_id') ? [] : balances, url);
      else if (url.pathname === '/api/v1/purchasing/orders') result = pageOf(orders, url);
      else if (url.pathname === '/api/v1/sales') result = pageOf(products.map(p => ({ id: p.id, invoice_no: 'INV-' + p.id, sold_at: '2026-11-05T10:00:00Z', customer_id: '0000000123', grand_total: '12345678901234567890.1234', paid_total: '0.0000', payment_status: 'unpaid' })), url);
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

  async function fill(selector, value) { await evaluate('document.querySelector(' + JSON.stringify(selector) + ').focus()'); await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'a', code: 'KeyA', modifiers: 2, windowsVirtualKeyCode: 65 }); await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'a', code: 'KeyA', modifiers: 2, windowsVirtualKeyCode: 65 }); await send('Input.insertText', { text: value }); }
  async function choose(selector, value) { await evaluate('(()=>{const e=document.querySelector(' + JSON.stringify(selector) + ');e.value=' + JSON.stringify(value) + ';e.dispatchEvent(new Event("change",{bubbles:true}));})()'); }
  async function toggleDetails(title) { await evaluate('(()=>{const s=[...document.querySelectorAll("summary")].find(s=>s.textContent.startsWith(' + JSON.stringify(title) + '));if(!s)throw Error("Missing summary");s.click()})()'); }
  for (const theme of ['light', 'dark']) {
    await viewport(); await navigate();
    if (await evaluate('document.documentElement.dataset.theme!==' + JSON.stringify(theme))) await click(theme === 'dark' ? 'Switch to dark theme' : 'Switch to light theme');
    const views = [
      ['products','/catalog','main tbody tr'],
      ['product-form','/catalog/new','[role="dialog"] [name="variant_sku"]'],
      ['inventory','/inventory','main tbody tr'],
      ['inventory-form','/inventory','main tbody tr','New adjustment'],
      ['purchase-orders','/purchasing','main tbody tr'],
      ['purchase-form','/purchasing','main tbody tr','New purchase order'],
      ['setup','/setup/units','main tbody tr'],
      ['sales','/sales?own=1&branch_id=1','main tbody tr'],
      ['customers','/operations/customers','main tbody tr'],
    ];
    for (const [name, route, selector, trigger] of views) {
      stage = theme + ' ' + name; console.log(stage); await viewport(); await navigate(route, selector);
      if (trigger) { await click(trigger); await until('!!document.querySelector("[role=dialog] form")'); }
      await measure(name + '-' + theme); await capture(name + '-' + theme + '-1440');
      await contrast(name + '_' + theme + '_contrast');
      if (name === 'products') {
        await assert('document.querySelector(".list-record-count").textContent==="Showing 1–20 · 45 total"&&document.querySelectorAll("tbody tr").length===20', theme + '_first_server_page_only');
        await assert('getComputedStyle(document.querySelector("tbody tr:nth-child(2)")).height==="44px"&&getComputedStyle(document.querySelector("td")).fontSize==="13px"', theme + '_compact_table_sizes');
        await evaluate('document.querySelector(".table-viewport-sticky").scrollTop=500'); await delay(100);
        await assert('(()=>{const v=document.querySelector(".table-viewport-sticky"),h=v.querySelector("th");return v.scrollTop>0&&Math.abs(h.getBoundingClientRect().top-v.getBoundingClientRect().top)<3})()', theme + '_sticky_header_within_table');
        await evaluate('document.querySelector(".table-viewport-sticky").scrollTop=0');
        await click('Next'); await until('document.querySelector(".list-record-count")?.textContent==="Showing 21–40 · 45 total"');
        checks[theme + '_next_page_server_range'] = true;
        await fill('[aria-label="Search products"]','missing'); await click('Search'); await until('document.querySelector(".list-empty-state h2")?.textContent==="No matching products"');
        await assert('document.querySelector(".filter-chip").textContent==="Search: missing"&&!document.querySelector("tbody tr")',theme+'_no_match_and_filter_chip'); await capture('products-' + theme + '-no-match');
        await click('Clear filters'); await until('document.querySelector(".list-record-count")?.textContent==="Showing 1–20 · 45 total"');
        await assert('!document.querySelector(".applied-filters")&&document.querySelector("[name=search]").value===""',theme+'_clear_resets_search_and_page');
      }
      if (name === 'product-form') {
        await evaluate('document.querySelector(".form-details summary").focus()');
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: ' ', code: 'Space', text: ' ', windowsVirtualKeyCode: 32 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: ' ', code: 'Space', windowsVirtualKeyCode: 32 });
        await until('document.querySelector(".form-details").open');
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
        await assert('document.activeElement.name==="variant_tracking_mode"',theme+'_keyboard_expands_and_enters_optional_section');
        await toggleDetails('Variant settings');
        await assert('document.querySelectorAll("[role=dialog] .form-section").length===4&&!document.querySelector("[role=dialog] details").open',theme+'_form_sections_and_optional_disclosure');
        await fill('[name=name]','Synthetic new item'); await fill('[name=variant_sku]','SYNTH-NEW'); await choose('[name=variant_base_unit_id]','1');
        await fill('[name=variant_default_price]','12.3456'); await toggleDetails('Additional product details'); await fill('textarea[name=description]','Keep optional details'); await toggleDetails('Additional product details');
        mode='validation'; await click('Create product'); await until('document.querySelector("textarea[name=description]")?.getAttribute("aria-invalid")==="true"');
        await assert('document.querySelector("textarea[name=description]").closest("details").open&&document.activeElement.name==="description"&&document.querySelector("[name=variant_default_price]").value==="12.3456"',theme+'_server_error_reveals_optional_field_and_keeps_values');
        await assert('document.querySelector("[role=dialog] form fieldset").firstElementChild.classList.contains("validation-summary")',theme+'_error_summary_at_form_top');
        await capture('product-form-' + theme + '-validation'); mode='ready';
        await navigate(route, selector);
      }
      if (name === 'inventory') await assert('document.querySelector("td").textContent==="SCHOOL-EXTRA-LONG-SKU-000000000000000000001"&&document.querySelector("td.numeric").textContent==="12345678901234567890.1234"&&getComputedStyle(document.querySelector("td.numeric")).textAlign==="end"',theme+'_exact_inventory_identifiers_and_quantities');
      if (name === 'sales') await assert('document.querySelector(".applied-filters a").getAttribute("href")==="/sales?own=1"',theme+'_clear_keeps_personal_view');
      if (name === 'customers') await assert('[...document.querySelectorAll("td")].some(e=>e.textContent==="+92 030012345678")&&getComputedStyle(document.querySelector("td:nth-child(2)")).textAlign!=="end"',theme+'_phone_is_not_a_number');
      for (const width of [360,768,1280,1920]) { await viewport(width); await assert('document.documentElement.scrollWidth<=innerWidth+1',name+'_'+theme+'_'+width+'_reflow'); if(width===360||width===1920) await capture(name+'-'+theme+'-'+width); }
      if (name === 'product-form') await assert('getComputedStyle(document.querySelector(".form-grid")).gridTemplateColumns.split(" ").length===2',theme+'_desktop_form_two_columns');
      await viewport(360);
      if (!name.includes('form')) await assert('document.querySelector("thead").getBoundingClientRect().height<=44',theme+'_'+name+'_mobile_headings_remain_readable');
      if (name.includes('form')) await assert('[...document.querySelectorAll("[role=dialog] input:not([type=checkbox]):not([type=hidden]),[role=dialog] select,[role=dialog] button")].filter(e=>e.getBoundingClientRect().width).every(e=>e.getBoundingClientRect().height>=48)',theme+'_'+name+'_mobile_targets');
      for(const zoom of [1.25,2]) { await viewport(1440,zoom); await assert('document.documentElement.scrollWidth<=innerWidth+1',name+'_'+theme+'_'+zoom+'_zoom_reflow'); if(zoom===2) await capture(name+'-'+theme+'-zoom-200'); }
    }
    await viewport(); await navigate('/catalog','main tbody tr'); await click('Edit product'); await until('!!document.querySelector("[role=dialog] [name=name]")');
    await assert('!document.querySelector("[name=variant_sku]")&&document.querySelector("textarea[name=description]").value==="Original optional description"',theme+'_edit_reuses_product_dialog');
    await fill('[role=dialog] [name=name]','Updated synthetic item'); await click('Save product'); await until('!document.querySelector("[role=dialog]")&&document.querySelector("tbody").textContent.includes("Updated synthetic item")');
    checks[theme+'_edit_saves_and_refreshes']=true;
    mode='empty'; await navigate('/catalog','.empty-state'); await assert('document.querySelector(".empty-state h2").textContent==="Your catalog starts here"',theme+'_empty_catalog'); await capture('products-'+theme+'-empty');
    mode='error'; await navigate('/catalog','main [role=alert]'); await assert('!document.querySelector(".empty-state")',theme+'_error_is_not_empty'); await capture('products-'+theme+'-error'); mode='ready'; await click('Try again'); await until('!!document.querySelector("tbody tr")');
    checks[theme+'_retry']=true;
    await navigate('/inventory?location_id=3','.empty-state'); await assert('document.querySelector(".empty-state h2").textContent==="No matching records"&&document.querySelector(".filter-chip").textContent==="Location: 3"',theme+'_inventory_filtered_empty'); await capture('inventory-'+theme+'-no-match');
  }
  const writes=requests.filter(r=>r.method!=='GET');
  if(!writes.every(r=>r.path.startsWith('/api/v1/products'))) throw Error('Unexpected fixture mutation');
  if(!writes.filter(r=>r.method==='POST').every(r=>r.body.variant.default_price==='12.3456'&&r.body.description==='Keep optional details')) throw Error('Form data changed');
  checks.unchanged_decimal_and_optional_payload=true;
  if (errors.length) throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(audit, `refinement-ux2-${phase}.json`), JSON.stringify({ passed: true, phase, fixture_only: true, working_database_writes: 0, checks, screenshots, measurements, requests }, null, 2)); console.log(JSON.stringify({ passed: true, phase, checks: Object.keys(checks).length, screenshots: screenshots.length }));
} catch (failure) { try { await capture('failure'); } catch {} fs.writeFileSync(path.join(privateDir, `refinement-ux2-${phase}-error.json`), JSON.stringify({ stage, message: failure.message, errors, recent_requests: requests.slice(-15) }, null, 2)); console.error(`${stage}: ${failure.message}`); process.exitCode = 1; }
finally { if (socket?.readyState === WebSocket.OPEN) { try { await send('Browser.close'); } catch {} socket.close(); } if (chrome.exitCode === null) chrome.kill(); server.closeAllConnections(); server.close(); }

