// Visual/density checks use synthetic HTTP responses only; never the POS database.
import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { dashboardFixture, fixtureTenant } from '../src/test/fixtures/businessDashboard.js';

const frontend = path.resolve(import.meta.dirname, '..'), root = path.dirname(frontend);
const phase = process.argv.includes('--before') ? 'before' : 'after';
const output = path.join(root, 'docs/design/refinement/ux5', phase), audit = path.join(root, 'docs/audit'), privateDir = path.join(audit, 'private');
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
let mode = 'ready'; const checkoutAttempts = []; let unconfirmedOnce = false;
const assetRequests=[];
const requests = [], errors = [], screenshots = [], checks = {}, measurements = {};

const cases = {
 owner: { profile: 'owner', title: 'Business Owner Dashboard' },
 admin: { profile: 'admin', title: 'Business Admin Dashboard' },
 manager: { profile: 'manager', title: 'Branch Manager Dashboard', widgets: ['sales','inventory','approvals','sales-trend','alerts','recent-sales','staff-activity'] },
 cashier: { profile: 'cashier', title: 'Cashier Dashboard', widgets: ['cashier'] },
 inventory: { title: 'Inventory workspace', permissions: ['dashboard.view','products.view','inventory.view','inventory.adjust','purchases.view','purchases.manage'], widgets: ['inventory','purchasing','alerts'] },
 purchasing: { title: 'Purchasing workspace', permissions: ['dashboard.view','purchases.view','purchases.manage'], widgets: ['purchasing'] },
 accounting: { title: 'Accounting overview', permissions: ['dashboard.view','reports.view','sales.view','expenses.view'], widgets: ['sales','accounting','sales-trend','branch-performance'] },
 viewer: { title: 'Workspace overview', permissions: ['dashboard.view','products.view'], widgets: [] },
 custom: { title: 'Inventory workspace', permissions: ['dashboard.view','products.view','inventory.view','inventory.adjust','purchases.view','purchases.manage','sales.view'], widgets: ['inventory','purchasing','alerts','sales','sales-trend'] },
};
function configure(name) { const item=cases[name], base=fixtureTenant(); Object.assign(tenant,base,{dashboard:{...base.dashboard,profile:item.profile||null,allowed_widgets:item.widgets||base.dashboard.allowed_widgets,held_sales:true},permissions:item.permissions||base.permissions,roles:[{id:900,code:name,name:'Synthetic '+name}]}); }
function responseFor(path) {
 const url=new URL(path,'http://fixture.test'), resource=url.pathname.split('/').at(-1);
 const base=dashboardFixture(path.replace(/(cashier-summary|staff-activity|recent-sales)(\?|$)/,'summary$2'),tenant);
 if(resource==='summary') {
   Object.assign(base.data.inventory,{recorded_skus:4820,pending_counts:4,quantities_by_unit:[{unit_id:1,unit:'EA',on_hand:'12345678901234567890.1234',reserved:'2.0000',available:'12345678901234567888.1234'},{unit_id:2,unit:'KG',on_hand:'15.5000',reserved:'0.0000',available:'15.5000'}]});
   Object.assign(base.data.purchasing,{draft_orders:2,suppliers_on_orders:18,outstanding_payables:'12345678901234567890.1234'});
   Object.assign(base.data.accounting,{posted_expenses:'1250.00',external_collections:'2000.00',external_refunds:'50.00',exchange_credit_in:'30.00',exchange_credit_out:'10.00',current_balances:{receivables:'15.0000',refunds_due:'2.0000'}});
   for(const key of ['sales','inventory','purchasing','accounting','shift','approvals']) if(!tenant.dashboard.allowed_widgets.includes(key)) delete base.data[key];
 }
 const receipt={id:31,invoice_no:'SYNTH-31',sold_at:'2026-11-05T09:00:00Z',grand_total:'2875.00',currency_code:'PKR',sale_status:'completed',payment_status:'paid'};
 const pagination={current_page:1,last_page:1,per_page:5,total:1};
 if(resource==='recent-sales')base.data={items:[receipt],pagination};
 if(resource==='staff-activity')base.data={items:[{membership_id:7,name:'Synthetic cashier',invoice_count:1,net_sales_excluding_tax:'77.65',open_sessions:1}],pagination};
 if(resource==='cashier-summary'){base.meta.sales_scope='own';base.data={open_sessions:1,sessions:[{id:5,register_name:'Front counter',branch_id:1,branch_name:'Main Store'}],selected_session:{id:5,register_name:'Front counter',branch_id:1,branch_name:'Main Store',opened_at:'2026-11-05T08:00:00Z'},shift_totals:{invoice_count:1,net_invoiced_including_tax:'2875.00'},held_carts:{count:2},receipts:{items:[receipt],pagination}};}
 return base;
}

const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://fixture.test');
    if (url.pathname.startsWith('/api/')) {
      if(mode==='slow')await delay(500);
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
      else if (url.pathname.startsWith('/api/v1/dashboard/')) result = responseFor(url.pathname.slice('/api/v1/'.length) + url.search);
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
      else if (url.pathname === '/api/v1/cash/sessions') result = paged([{ id: 5, register_id: 2, register_name: 'Front counter', branch_id: 1, branch_name: 'Main Store' }]);
      else if (url.pathname === '/api/v1/operations/payment-methods') result = paged([{ id: 1, name: 'Cash', method_type: 'cash', active: true }]);
      else if (url.pathname === '/api/v1/pos/products') result = paged(mode === 'long-cart' ? Array.from({ length: 20 }, (_, i) => ({ id: i + 9, name: 'Cotton shirt ' + (i + 1), sku: 'SYNTH-SHIRT-' + i, default_price: '1250.00' })) : [{ id: 9, name: 'Cotton shirt', sku: 'SYNTH-SHIRT', default_price: '1250.00' }]);
      else if (url.pathname === '/api/v1/pos/checkout') { checkoutAttempts.push(JSON.parse(raw)); if (mode === 'unconfirmed' && !unconfirmedOnce) { unconfirmedOnce = true; res.statusCode = 503; result = { message: 'Synthetic checkout result unconfirmed.' }; } else if (mode === 'rejected') { res.statusCode = 409; result = { message: 'Synthetic stock changed. Request a fresh quote.' }; } else result = { data: { id: 81, invoice_no: 'SYNTH-81' } }; }
      else if (url.pathname === '/api/v1/pos/quotes') result = { data: { id: 51, expires_at: new Date(Date.now() + (mode === 'expired' ? -1000 : mode === 'unconfirmed' ? 6000 : 600000)).toISOString(), quote: { subtotal: '2500.00', discount_total: '0.00', tax_total: '375.00', grand_total: '2875.00', currency_code: 'PKR', currency_scale: 2, allow_credit: true } } };
      else if (url.pathname === '/api/v1/pos/held-carts') result = paged([]);
      else if (url.pathname === '/api/v1/reports/summary') result = { data: { period: { from: url.searchParams.get('from'), to: url.searchParams.get('to'), timezone: tenant.timezone }, totals: { net_sales_excluding_tax: '1234567890.1200', return_credits: '1750.00', net_tax: '375.00' }, methods: { Cash: { collections: '2875.00', refunds: '0.00' } }, current_balances: { due: '1250.00', refund_due: '0.00' }, reconciled: true, basis: 'Synthetic posted-value report.' } };
      else result = paged([]);
      res.end(JSON.stringify(result)); return;
    }
    if(url.pathname.endsWith('.js'))assetRequests.push(url.pathname);
    const dist = path.join(frontend, 'dist'), candidate = path.resolve(dist, decodeURIComponent(url.pathname).replace(/^\/+/, ''));
    if (candidate !== dist && !candidate.startsWith(dist + path.sep)) { res.statusCode = 403; res.end(); return; }
    const file = fs.existsSync(candidate) && fs.statSync(candidate).isFile() ? candidate : path.join(dist, 'index.html');
    res.setHeader('Content-Type', ({ '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.woff': 'font/woff', '.woff2': 'font/woff2' })[path.extname(file)] || 'application/octet-stream'); res.end(fs.readFileSync(file));
  } catch (failure) { errors.push(failure.message); res.statusCode = 500; res.end('{}'); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const profile = fs.mkdtempSync(path.join(privateDir, `ux5-${phase}-chrome-`));
const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let socket, sequence = 0, stage = 'startup'; const pending = new Map();
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function send(method, params = {}) { const id = ++sequence; return new Promise((resolve, reject) => { const timer = setTimeout(() => { pending.delete(id); reject(Error(`CDP timeout: ${method}`)); }, 30000); pending.set(id, { resolve: result => { clearTimeout(timer); resolve(result); }, reject }); socket.send(JSON.stringify({ id, method, params })); }); }
async function evaluate(expression) { const r = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (r.exceptionDetails) throw Error(r.exceptionDetails.exception?.description || r.exceptionDetails.text); return r.result.value; }
async function until(expression) { const start = Date.now(); while (Date.now() - start < 20000) { if (await evaluate(expression)) return; await delay(100); } throw Error(`Timed out: ${expression}`); }
async function navigate(route = '/', selector = '.business-dashboard') { await evaluate('window.__compactNavigationPending=true'); await send('Page.navigate', { url: origin + route }); await until(`!window.__compactNavigationPending&&document.readyState==='complete'&&!!document.querySelector(${JSON.stringify(selector)})`); await evaluate('document.fonts.ready'); }
async function viewport(width = 1440, zoom = 1) { await send('Emulation.setDeviceMetricsOverride', { width: Math.round(width / zoom), height: Math.round(1000 / zoom), deviceScaleFactor: zoom, mobile: false }); await evaluate('window.scrollTo(0,0)'); await delay(100); }
async function capture(name) { await until('!document.querySelector(".product-results[aria-busy=true]")'); await evaluate('[...document.querySelectorAll(".Toastify__close-button")].forEach(button=>button.click())'); await delay(350); const r = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); fs.writeFileSync(path.join(output, `${name}.png`), Buffer.from(r.data, 'base64')); screenshots.push(`${name}.png`); }
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




 async function key(key,code,vk,text){await send('Input.dispatchKeyEvent',{type:'keyDown',key,code,windowsVirtualKeyCode:vk,...(text?{text}:{})});await send('Input.dispatchKeyEvent',{type:'keyUp',key,code,windowsVirtualKeyCode:vk});}
 async function link(label,scope='main'){const b=await evaluate('(()=>{const e=[...document.querySelectorAll('+JSON.stringify(scope+' a')+')].find(e=>e.textContent.trim()==='+JSON.stringify(label)+');if(!e)throw Error("Missing link");e.click();})()');}
 for(const theme of ['light','dark']) {
   configure('owner');stage=theme+' dashboard';await viewport();await navigate();
   if(await evaluate('document.documentElement.dataset.theme!=='+JSON.stringify(theme)))await click(theme==='dark'?'Switch to dark theme':'Switch to light theme');
   await capture('dashboard-'+theme+'-1440');
   if(phase==='after') {
     await assert('document.title==="Business Owner Dashboard · Universal POS"',theme+'_role_page_title');
     const start=requests.length;await click('Apply period');await delay(350);
     if(requests.length!==start)throw Error('Unchanged period reloaded dashboard');checks[theme+'_unchanged_period_no_requests']=true;
     await contrast(theme+'_dashboard_contrast');
     if(assetRequests.some(path=>/InventoryPage-|PurchasingPage-|BillingPage-|ReportsPage-|TeamPage-/.test(path)))throw Error('Unopened management page was downloaded');
     checks[theme+'_unopened_pages_not_downloaded']=true;
   }
   stage=theme+' route keyboard focus';await evaluate('[...document.querySelectorAll("#workspace-navigation a")].find(e=>e.getAttribute("href")==="/catalog").focus()');await key('Enter','Enter',13,'\r');await until('!!document.querySelector(".list-pagination")');
   if(phase==='after') {
     await until('document.activeElement===document.querySelector("main h1")');
     await assert('document.querySelectorAll("main tbody tr").length===20',theme+'_catalog_uses_one_server_page');
     await assert('document.title===document.querySelector("main h1").textContent+" · Universal POS"',theme+'_route_title_and_focus');
     await assert('getComputedStyle(document.activeElement).outlineStyle!=="none"',theme+'_heading_focus_visible');
     await assert('document.querySelector(".list-record-count").getAttribute("role")==="status"&&document.querySelector(".list-record-count").getAttribute("aria-atomic")==="true"',theme+'_page_count_live_region');
   }
   await capture('catalog-focus-'+theme+'-1440');
   stage=theme+' form keyboard';await click('Add product');await until('!!document.querySelector("[role=dialog] input[name=name]")');
   if(phase==='after') {
     await assert('document.querySelector("[role=dialog]").contains(document.activeElement)&&document.activeElement.name==="name"',theme+'_modal_initial_field');
     await key('Tab','Tab',9);await assert('document.querySelector("[role=dialog]").contains(document.activeElement)',theme+'_modal_tab_contained');
   }
   await capture('product-form-'+theme+'-1440');
   if(phase==='after') {
     const ax=await send('Accessibility.getFullAXTree');
     if(!ax.nodes.some(node=>!node.ignored&&node.role?.value==='dialog'&&node.name?.value==='Add product'))throw Error('Dialog name missing from accessibility tree');
     if(ax.nodes.some(node=>!node.ignored&&node.role?.value==='navigation'&&node.name?.value==='Main navigation'))throw Error('Background navigation remains exposed through modal');
     checks[theme+'_dialog_accessibility_tree']=true;
     for(const [width,zoom,suffix] of [[360,1,'360'],[1440,2,'zoom-200']]) {await viewport(width,zoom);await assert('document.documentElement.scrollWidth<=innerWidth+1',theme+'_form_'+suffix+'_reflows');await capture('product-form-'+theme+'-'+suffix);}
     await viewport();
   }
   await key('Escape','Escape',27);
   if(phase==='after')await assert('document.activeElement.textContent.includes("Add product")',theme+'_modal_returns_to_trigger');
   stage=theme+' keyboard skip';await navigate('/catalog','main h1');await evaluate('document.activeElement.blur()');await key('Tab','Tab',9);
   if(phase==='after') {await assert('document.activeElement.classList.contains("skip-link")',theme+'_skip_first');await key('Enter','Enter',13,'\r');await assert('document.activeElement.id==="workspace-content"',theme+'_skip_enters_content');}
   for(const width of [360,768,1280,1440,1920]){await viewport(width);if(phase==='after')await assert('document.documentElement.scrollWidth<=innerWidth+1',theme+'_'+width+'_catalog_reflows');if(width===360)await capture('catalog-'+theme+'-360');}
   if(phase==='after') {
     await viewport(360);await click('Open navigation');await until('!!document.querySelector("[role=dialog]")');
     await key('Tab','Tab',9);await assert('document.querySelector("[role=dialog]").contains(document.activeElement)',theme+'_drawer_keyboard');await key('Escape','Escape',27);await assert('document.activeElement.getAttribute("aria-label")==="Open navigation"',theme+'_drawer_focus_restored');
     for(const zoom of [1.25,2]){await viewport(1440,zoom);await assert('document.documentElement.scrollWidth<=innerWidth+1',theme+'_'+zoom+'_zoom_reflows');await capture('catalog-'+theme+'-zoom-'+Math.round(zoom*100));}
     await viewport();await contrast(theme+'_catalog_contrast');
   }
   // Each theme starts with a fresh browser document/asset request list.
   assetRequests.length=0;
 }
 if(phase==='after') {
   stage='reduced motion';await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]});
   tenant.permissions=[...tenant.permissions,'sales.discount'];
   mode='slow';await navigate('/pos?cash_session_id=5','.loading-state');
   await until('!!document.querySelector(".remix-icon-spin,.loading-skeleton,.global-loader__bar")');
   await assert('[...document.querySelectorAll(".remix-icon-spin,.loading-skeleton,.global-loader__bar")].every(e=>getComputedStyle(e).animationName==="none")','reduced_motion_loaders_static');
   await until('!!document.querySelector(".product-choice")');mode='ready';await evaluate('document.querySelector(".product-choice").click()');await click('Get server quote');await until('!!document.querySelector(".checkout-button")');
   await assert('document.querySelector(".app-shell").dataset.density==="comfortable"','pos_density_preserved');
   // Trigger a real client validation toast; motion is inspected without altering timing.
   await evaluate('(()=>{const e=document.querySelector("[name=discount_amount]");Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,"value").set.call(e,"invalid");e.dispatchEvent(new Event("input",{bubbles:true}));})()');await click('Get server quote');
   await until('!!document.querySelector(".Toastify__progress-bar")');
   await assert('getComputedStyle(document.querySelector(".Toastify__progress-bar")).visibility==="hidden"','reduced_motion_hides_countdown_without_disabling_timer');
   await assert('getComputedStyle(document.querySelector(".Toastify__progress-bar")).animationName!=="none"','toast_dismissal_timer_animation_preserved');
   await capture('pos-reduced-motion');
   await send('Emulation.setEmulatedMedia',{features:[]});
 }
 if(errors.length)throw Error(JSON.stringify(errors));
 const assets=fs.readdirSync(path.join(frontend,'dist/assets')).filter(name=>/^index-.*\.(js|css)$/.test(name)).map(name=>({name,bytes:fs.statSync(path.join(frontend,'dist/assets',name)).size}));
 fs.writeFileSync(path.join(audit,'refinement-ux5-'+phase+'.json'),JSON.stringify({passed:true,phase,fixture_only:true,working_database_writes:0,checks,screenshots,requests,assets},null,2));console.log(JSON.stringify({passed:true,phase,checks:Object.keys(checks).length,screenshots:screenshots.length}));
}catch(failure){try{await capture('failure');}catch{}fs.writeFileSync(path.join(privateDir,'refinement-ux5-'+phase+'-error.json'),JSON.stringify({stage,message:failure.message,errors,recent_requests:requests.slice(-10)},null,2));console.error(stage+': '+failure.message);process.exitCode=1;}
finally{if(socket?.readyState===WebSocket.OPEN){try{await send('Browser.close');}catch{}socket.close();}if(chrome.exitCode===null)chrome.kill();server.closeAllConnections();server.close();}
