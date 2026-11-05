import TableViewport from '../../components/ui/TableViewport';
import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { FormDialog } from "../../components/Modal";
import { useState } from 'react';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, Input, SaveForm, Select, ValidatedControl } from '../../components/WorkspaceForm';
import { RemoteTable, useResources } from './OperationUI';
export default function WorkspaceToolsPage() {
  const {
    user
  } = useAuth();
  const can = p => user.permissions.includes(p);
  const [tab, setTab] = useState(() => [['settings', 'settings.manage'], ['support', 'support.manage'], ['alerts', 'alerts.view'], ['imports', 'imports.manage'], ['exports', 'exports.manage'], ['audit', 'audit.view'], ['legacy', 'legacy.manage']].find(([, permission]) => can(permission))?.[0] || null);
  const [revision, setRevision] = useState(0);
  const [message, setMessage] = useState(null);
  const [error, setError] = useState(null);
  const refs = useResources(tab === 'settings' && can('settings.manage') ? ['workspace/settings', 'workspace/templates', 'workspace/operations'] : [], revision);
  const settings = refs.data['workspace/settings']?.data;
  const tabs = [['settings', 'Settings & alerts', 'settings.manage'], ['audit', 'Audit', 'audit.view'], ['alerts', 'Alerts', 'alerts.view'], ['imports', 'Imports', 'imports.manage'], ['exports', 'Exports', 'exports.manage'], ['support', 'Support', 'support.manage'], ['legacy', 'Legacy finance', 'legacy.manage']].filter(([,, p]) => can(p));
  async function act(path, body) {
    setError(null);
    try {
      await saveAction(path, body);
    } catch (failure) {
      setError(failure);
    }
  }
  async function saveAction(path, body) {
    const result = await workspaceApi.save(path, body);
    setMessage(result.data);
    setRevision(r => r + 1);
  }
  async function saveSettings(form) {
    await workspaceApi.save('workspace/settings', {
      receipt_name: form.get('receipt_name') || null,
      receipt_footer: form.get('receipt_footer'),
      tax_registration: form.get('tax_registration') || null,
      return_days: form.get('return_days') === '' ? null : Number(form.get('return_days')),
      low_stock_threshold: form.get('low_stock_threshold') || null
    }, true);
    setRevision(r => r + 1);
  }
  return <main className="content"><p className="eyebrow">BUSINESS ADMINISTRATION</p><h1>Workspace tools</h1><div className="tabs">{tabs.map(([key, label]) => <button key={key} className={tab === key ? '' : 'secondary'} onClick={() => {
        setTab(key);
        setMessage(null);
      }}><Icon name="list" />{label}</button>)}</div><ErrorNotice error={error || refs.error} /><LoadingState busy={Boolean(tab === 'settings' && refs.loading)} label="Loading workspace settings…" variant={settings ? 'inline' : 'form'} />{message && <WorkspaceResult result={message} />}{tab === 'settings' && settings && <><FormDialog title={<>Receipt and returns settings</>} trigger="Edit receipt and returns settings"><SaveForm onSave={saveSettings}><Input name="receipt_name" label="Receipt business name" value={settings.receipt_name} required={false} /><Input name="receipt_footer" label="Receipt footer" value={settings.receipt_footer} /><Input name="tax_registration" label="Tax registration number" value={settings.tax_registration} required={false} /><Input name="return_days" label="Return day limit (blank for no fixed limit)" type="number" value={settings.return_days} required={false} /><Input name="low_stock_threshold" label="Low-stock alert threshold (blank to disable)" value={settings.low_stock_threshold} required={false} /></SaveForm></FormDialog><FormDialog title={<>Low-stock alert template</>} trigger="Edit low-stock alert template"><SaveForm onSave={async form => {
          await workspaceApi.save('workspace/templates/stock.low', {
            body_template: form.get('body_template'),
            active: form.has('active')
          }, true);
          setRevision(r => r + 1);
        }}><Input name="body_template" label="Alert text" value={refs.data['workspace/templates']?.data.find(t => t.event_code === 'stock.low')?.body_template || '{{sku}}: {{quantity}} available'} /><p>Available fields: {'{{sku}}'}, {'{{quantity}}'}, {'{{business_name}}'}.</p><label className="checkbox"><ValidatedControl><input name="active" type="checkbox" defaultChecked={Boolean(refs.data['workspace/templates']?.data.find(t => t.event_code === 'stock.low')?.active ?? true)} aria-label={"Enable this template"} /></ValidatedControl>Enable this template</label></SaveForm></FormDialog><section className="card"><h2>Operations</h2><p>Billing: {refs.data['workspace/operations']?.data.stripe_configured ? 'Stripe configured' : 'Setup pending'}; live charging: {refs.data['workspace/operations']?.data.live_charging ? 'Enabled' : 'Disabled'}</p><p>Latest backup/operator run: {refs.data['workspace/operations']?.data.last_operator_run?.status || 'No scheduled run recorded'}</p></section></>}{tab === 'audit' && can('audit.view') && <RemoteTable path="workspace/audit" columns={[["occurred_at", "Time"], ["actor_user_id", "Actor"], ["action", "Action"], ["subject_type", "Subject"], ["subject_id", "Record"], ["details_json", "Details"]]} />} {tab === 'alerts' && can('alerts.view') && <RemoteTable path="workspace/alerts" columns={[["event_code", "Event"], ["body", "Message"], ["created_at", "Time"], ["read_at", "Read"]]} onSelect={row => act(`workspace/alerts/${row.id}/read`, {})} revision={revision} />} {tab === 'imports' && can('imports.manage') && <><FormDialog title={<>Import new catalog products</>} trigger="Import products"><p>UTF-8 CSV, up to 1,000 rows. Existing SKUs and stock quantities are preserved.</p><code>sku,name,unit_code,default_cost,default_price,product_type</code><SaveForm onSave={async form => {
          const body = new FormData();
          body.set('file', form.get('file'));
          const result = await workspaceApi.save('workspace/imports', body);
          setMessage(result.data);
          setRevision(r => r + 1);
        }} label="Validate import"><Input name="file" label="Catalog CSV file" type="file" accept=".csv,text/csv" /></SaveForm></FormDialog><RemoteTable path="workspace/imports" columns={[["id", "Batch"], ["status", "Status"], ["errors_json", "Validation issues"], ["posted_at", "Imported"]]} onSelect={row => {
        if (row.status === 'ready') act(`workspace/imports/${row.id}/post`, {});
      }} revision={revision} /></>}{tab === 'exports' && can('exports.manage') && <><FormDialog title={"Create private export"} trigger="Create private export"><SaveForm onSave={form => saveAction('workspace/exports', {
          type: form.get('type')
        })} label="Create private export"><Select name="type" label="Export type" options={['catalog', 'sales', 'customers', 'stock-movements']} /></SaveForm></FormDialog><RemoteTable path="workspace/exports" columns={[["id", "Export"], ["type", "Type"], ["row_count", "Rows"], ["expires_at", "Expires"]]} onSelect={row => {
        if (row.status === 'ready') window.open(`/api/v1/workspace/exports/${row.id}/download`, '_blank', 'noopener,noreferrer');
      }} revision={revision} /></>}{tab === 'support' && can('support.manage') && <><FormDialog title={<>Open support case</>} trigger="Open support case"><p>Describe the problem and include its request ID if available. Keep passwords and payment credentials out of the description.</p><SaveForm onSave={form => saveAction('workspace/support', Object.fromEntries(form))} label="Open support case"><Input name="subject" label="Subject" /><Input name="description" label="Description" /></SaveForm></FormDialog><RemoteTable path="workspace/support" columns={[["reference", "Case"], ["subject", "Subject"], ["status", "Status"], ["resolution", "Resolution"]]} revision={revision} /></>}{tab === 'legacy' && can('legacy.manage') && <section className="card"><h2>Preserve and reconcile legacy financials</h2><p>This snapshots original ledger, dealer, daily-account and sales evidence. Historical refunds remain unknown; it does not merge ledgers or migrate balances automatically.</p><button onClick={() => act('legacy/financial-reconciliation', {})}><Icon name="list" />Capture reconciliation evidence</button></section>}</main>;
}
function WorkspaceResult({
  result
}) {
  return <section className="card" role="status"><h2>{result.status === 'invalid' ? 'Review validation issues' : 'Operation completed'}</h2>{result.status && <p>Status: {result.status}</p>}{result.valid_rows !== undefined && <p>Valid rows: {result.valid_rows}</p>}{result.rows !== undefined && <p>Imported products: {result.rows}</p>}{result.errors?.length > 0 && <ul>{result.errors.map((error, index) => <li key={index}>Row {error.row}: {error.message}</li>)}</ul>}{result.refund_history && <><p>Historical refund evidence: {result.refund_history}</p><p>{result.note}</p><p>{result.issues.length} issues require review. Accounting migration {result.accounting_migration_allowed ? 'has an empty baseline' : 'requires source review'}.</p><TableViewport><table><thead><tr><th>Source</th><th>Preserved records</th></tr></thead><tbody>{Object.entries(result.tables).map(([table, evidence]) => <tr key={table}><td>{table}</td><td>{evidence.count}</td></tr>)}</tbody></table></TableViewport></>}</section>;
}
