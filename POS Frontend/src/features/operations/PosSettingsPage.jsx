import TableViewport from '../../components/ui/TableViewport';
import Modal from "../../components/Modal";
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, SaveForm, Input, ValidatedControl } from '../../components/WorkspaceForm';
import { RemoteTable, requestKey } from './OperationUI';
export default function PosSettingsPage() {
  const [branch, setBranch] = useState(null);
  const [preview, setPreview] = useState(null);
  const [error, setError] = useState(null);
  const [result, setResult] = useState(null);
  const [revision, setRevision] = useState(0);
  const [key, setKey] = useState(requestKey);
  async function open(row) {
    setError(null);
    setPreview(null);
    setBranch(row);
    setResult(null);
    setKey(requestKey());
    try {
      setPreview((await workspaceApi.get(`pos/branches/${row.id}/preview`)).data);
    } catch (failure) {
      setError(failure);
    }
  }
  async function activate(form) {
    const data = await workspaceApi.save(`pos/branches/${branch.id}/activate`, {
      idempotency_key: key,
      preview_hash: preview.preview_hash,
      confirm_locations: form.get('confirm') === 'on',
      allow_credit: form.get('credit') === 'on',
      tax_jurisdiction: form.get('jurisdiction') || null
    });
    setResult(data.data);
    setRevision(r => r + 1);
    setPreview(null);
  }
  return <main className="content"><p className="eyebrow">OWNER SETUP</p><h1>POS activation</h1><p>Review stock locations before switching a branch to the new POS. Catalog prices exclude tax. Fixed discounts apply before tax, with half-up rounding per line to currency minor units.</p><div className="form-actions"><Link to="/operations/registers">Cash registers</Link><Link to="/operations/payment-methods">Payment methods</Link><Link to="/setup/tax-rates">Tax rates</Link></div><RemoteTable path="catalog/branches" columns={[["name", "Branch"], ["pos_engine", "Checkout engine"], ["allow_credit", "Named-customer credit"]]} onSelect={open} revision={revision} /><ErrorNotice error={error} />{preview && <Modal size="large" title={<>Review {branch.name}</>} onClose={() => setPreview(null)}>{preview.legacy_freeze_scope && <p className="error">Activation freezes: {preview.legacy_freeze_scope} Historical invoices remain readable. This change imports current quantities once and is not reversible through this screen.</p>}<p>{preview.opening_rows.length} source stock rows will receive opening entries.</p>{preview.issues.map(issue => <p role="alert" className="error" key={issue}>{issue}</p>)}{preview.opening_rows.length > 0 && <TableViewport className="table-scroll"><table><thead><tr><th>Legacy stock</th><th>Variant</th><th>Location</th><th className="numeric">Opening quantity</th></tr></thead><tbody>{preview.opening_rows.map(row => <tr key={row.stock_id}><td>{row.stock_id}</td><td>{row.variant_id}</td><td>{row.location_id}</td><td className="numeric">{row.quantity}</td></tr>)}</tbody></table></TableViewport>}<SaveForm onSave={activate} label="Approve pricing and activate branch" onCancel={() => setPreview(null)} readOnly={preview.issues.length > 0}><Input name="jurisdiction" label="Tax jurisdiction code (required for taxed products)" value={branch.tax_jurisdiction} required={false} maxLength={80} /><label className="checkbox"><ValidatedControl><input type="checkbox" name="credit" defaultChecked={!!branch.allow_credit} aria-label={"Allow named customers to leave a balance due. Walk-ins must pay in full."} /></ValidatedControl>Allow named customers to leave a balance due. Walk-ins must pay in full.</label><label className="checkbox"><ValidatedControl><input type="checkbox" name="confirm" required aria-label={"I reviewed the physical stock locations, opening quantities and pricing policy."} /></ValidatedControl>I reviewed the physical stock locations, opening quantities and pricing policy.</label></SaveForm></Modal>}{result && <p role="status" className="card">Branch activated. Imported {result.opening_rows} opening entries. <Link to="/cash">Open a register shift</Link>.</p>}</main>;
}
