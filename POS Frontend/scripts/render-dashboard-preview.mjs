// Isolated design-review fixture. Never imported by the production app/router.
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { createServer } from 'vite';
const frontend = path.resolve(import.meta.dirname, '..');
const project = path.dirname(frontend);
const require = createRequire(path.join(frontend, 'package.json'));
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');
const { StaticRouter } = require('react-router-dom/server');
const h = React.createElement;
const vite = await createServer({ root: frontend, server: { middlewareMode: true, hmr: false }, optimizeDeps: { noDiscovery: true, include: [], entries: [] }, appType: 'custom' });
try {
  const read = async name => (await vite.ssrLoadModule(`/src/components/dashboard/${name}.jsx`)).default;
  const [PageHeader, KpiCard, ChartCard, TrendChart, AlertList, QuickActionGrid, EmptyState] = await Promise.all(
    ['PageHeader', 'KpiCard', 'ChartCard', 'TrendChart', 'AlertList', 'QuickActionGrid', 'EmptyState'].map(read));
  const period = { from: '2026-11-01', to: '2026-11-03' }, timezone = 'Asia/Karachi';
  // Explicitly synthetic examples, only served by the local browser fixture.
  const series = [{ label: 'Nov 1', value: '1250.50' }, { label: 'Nov 2', value: '1875.25' }, { label: 'Nov 3', value: '1430.00' }];
  const content = h(StaticRouter, { location: '/' }, h('main', { className: 'content', style: { maxWidth: 1280 } },
    h(PageHeader, { eyebrow: 'ISOLATED DESIGN PREVIEW · SYNTHETIC FIXTURE DATA', title: 'Shared dashboard components', description: 'D1 component review. These figures are not connected to a business or database.' }),
    h('div', { className: 'dashboard-kpi-grid' },
      h(KpiCard, { title: 'Net sales excluding tax', value: '4555.75', currency: 'PKR', format: 'money', icon: 'sales', definition: 'Preview of a server-supplied reporting total.', period, timezone }),
      h(KpiCard, { title: 'Transactions', value: '18', icon: 'list', definition: 'Count definition comes from the future API.', period, timezone }),
      h(KpiCard, { title: 'Zero activity', value: '0', icon: 'inventory', definition: 'A verified zero stays distinct from unavailable data.' }),
      h(KpiCard, { title: 'Verified gross profit', icon: 'reports', definition: 'Unavailable until reliable cost accounting is supplied.' })),
    h('div', { className: 'dashboard-chart-grid' },
      h(ChartCard, { title: 'Sales trend · fixture', description: 'Presentation preview only; no live reporting endpoint.', unit: 'PKR, excluding tax', period, timezone },
        h(TrendChart, { title: 'Synthetic net sales', data: series, unit: 'PKR', currency: 'PKR', locale: 'en-US' })),
      h(AlertList, { title: 'Exception list · fixture', timezone, alerts: [{ id: 'example', title: 'Example stock alert', message: 'Review the source record before taking action.', severity: 'warning', to: '/inventory' }] })),
    h(QuickActionGrid, { permissions: ['sales.create', 'products.manage', 'reports.view'], actions: [
      { label: 'New sale', icon: 'pos', to: '/pos', permission: 'sales.create' },
      { label: 'Add product', icon: 'product', to: '/catalog/new', permission: 'products.manage' },
      { label: 'Reports', icon: 'reports', to: '/reports', permission: 'reports.view' },
    ] }),
    h('section', { className: 'card', style: { marginTop: 24 } }, h(EmptyState, { title: 'No data for the selected period', description: 'An empty-state component, without invented totals.' }))));
  const markup = renderToStaticMarkup(content);
  const stylesheets = fs.readdirSync(path.join(frontend, 'dist', 'assets')).filter(name => name.endsWith('.css'));
  const directory = path.join(project, 'docs', 'audit', 'private');
  fs.mkdirSync(directory, { recursive: true });
  for (const theme of ['light', 'dark']) {
    const html = `<!doctype html><html lang="en" class="${theme === 'dark' ? 'dark' : ''}"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>D1 isolated component review</title>${stylesheets.map(name => `<link rel="stylesheet" href="/assets/${name}">`).join('')}</head><body>${markup}</body></html>`;
    fs.writeFileSync(path.join(directory, `d1-components-${theme}.html`), html);
  }
  console.log('Rendered isolated light/dark component previews with explicitly synthetic fixture data.');
} finally { await vite.close(); }
