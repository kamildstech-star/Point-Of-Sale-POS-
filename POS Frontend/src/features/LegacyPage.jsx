import { FormDialog } from "../components/Modal";
import LoadingState from '../components/LoadingState';
import { useEffect, useState } from 'react';
import LegacyEvidence from './LegacyEvidence';
import LegacyCustomers from './operations/LegacyCustomers';
import { useAuth } from '../auth/AuthProvider';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice, SaveForm, Select, ValidatedControl } from '../components/WorkspaceForm';
export default function LegacyPage() {
  const {
    user
  } = useAuth();
  const [state, setState] = useState({
    loading: true
  });
  async function load() {
    setState(previous => ({ ...previous, loading: true, error: null }));
    try {
      const [report, locations] = await Promise.all([workspaceApi.get('legacy/catalog'), workspaceApi.get('catalog/locations')]);
      setState({
        report: report.data,
        locations: locations.data
      });
    } catch (error) {
      setState(previous => ({ ...previous, loading: false, error }));
    }
  }
  useEffect(() => {
    load();
  }, []);
  const manage = user.permissions.includes('legacy.manage');
  const summary = state.report?.batch?.reconciliation_json;
  const counts = typeof summary === 'string' ? JSON.parse(summary) : summary;
  return <main className="content"><p className="eyebrow">LEGACY RECONCILIATION</p><h1>Preserve your catalog history</h1><p className="muted">Review the mapping from existing school stock to universal variants.</p><ErrorNotice error={state.error} onRetry={load} /><LoadingState busy={Boolean(state.loading)} label="Loading mapping report…" variant={state.report ? 'inline' : 'cards'} rows={3} />{state.report && <><div className="cards" aria-busy={Boolean(state.loading)}><article className="card"><h3>Source stock rows</h3><strong>{state.report.stocks.total}</strong></article><article className="card"><h3>Mapped rows</h3><strong>{counts?.mapped ?? 'Not run'}</strong></article><article className="card"><h3>Needs review</h3><strong>{counts?.unresolved ?? 'Not run'}</strong></article></div><section className="card editor-card"><h2>School-specific SKUs</h2><p>Each school, product and size stock row keeps its own SKU. School names, source prices, images, enum values and original IDs remain in its mapping evidence.</p><p>Policy: {state.report.sku_policy === 'distinct_by_school' ? 'Distinct SKUs approved' : 'Owner approval required'}</p>{manage && !state.report.sku_policy && <FormDialog title={"Approve distinct SKUs"} trigger="Approve distinct SKUs"><SaveForm label="Approve distinct SKUs" onSave={async form => {
            await workspaceApi.save('legacy/sku-policy', {
              policy: 'distinct_by_school',
              confirmed: form.has('confirmed')
            });
            await load();
          }}><label className="checkbox-label"><ValidatedControl><input name="confirmed" type="checkbox" required aria-label={"I approve a separate SKU for each legacy stock row."} /></ValidatedControl>I approve a separate SKU for each legacy stock row.</label></SaveForm></FormDialog>}</section>{manage && <FormDialog title={<>Reconcile stock mappings</>} trigger="Reconcile stock mappings"><p>This creates catalog references and documents exceptions. Quantities and historical orders remain unchanged; opening stock is imported once through POS activation after reviewing physical locations.</p><SaveForm label="Run reconciliation" onSave={async form => {
          await workspaceApi.save('legacy/catalog-map', {
            location_id: form.get('location_id')
          });
          await load();
        }}><Select name="location_id" label="Target stock location" options={state.locations} /></SaveForm></FormDialog>}<LegacyEvidence report={state.report} />{user.permissions.includes('customers.view') && <LegacyCustomers manage={manage} />}</>}</main>;
}
