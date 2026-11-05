import Icon from '../../components/Icon';
import Modal from "../../components/Modal";
import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, Input, SaveForm, ValidatedControl } from '../../components/WorkspaceForm';
import LookupSelect from '../../components/LookupSelect';
import { RemoteTable, useResources } from './OperationUI';
import RecordActions, { DeleteDialog } from '../../components/RecordActions';
export default function OperationsReferencePage() {
  const {
    resource
  } = useParams();
  const {
    user
  } = useAuth();
  const [record, setRecord] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [revision, setRevision] = useState(0);
  const config = {
    customers: ['Customers', 'customers.manage'],
    suppliers: ['Suppliers', 'purchases.manage'],
    registers: ['Cash registers', 'settings.manage'],
    'payment-methods': ['Payment methods', 'settings.manage']
  }[resource];
  const refs = useResources(resource === 'registers' ? ['catalog/branches', 'catalog/locations'] : []);
  if (!config) return <main className="content"><h1>Page not found</h1></main>;
  const canWrite = user.permissions.includes(config[1]);
  async function save(form) {
    const body = Object.fromEntries(form);
    body.active = form.get('active') === 'on';
    if (resource === 'suppliers') body.payment_terms_days = Number(body.payment_terms_days);
    if (resource === 'registers') {
      body.branch_id = Number(body.branch_id);
      body.location_id = Number(body.location_id);
    }
    await workspaceApi.save(`operations/${resource}${record?.id ? `/${record.id}` : ''}`, body, !!record?.id);
    setRecord(null);
    setRevision(r => r + 1);
  }
  return <main className="content" key={resource}><p className="eyebrow">RETAIL WORKSPACE</p><h1>{config[0]}</h1><ErrorNotice error={refs.error} />{canWrite && <button onClick={() => setRecord({})}><Icon name="add" />Add {config[0].toLowerCase().replace(/s$/, '')}</button>}<RemoteTable key={resource} path={`operations/${resource}`} columns={resource === 'registers' ? [['name', 'Register'], ['branch_id', 'Branch'], ['location_id', 'Stock location'], ['active', 'Active']] : [['name', 'Name'], ...(resource === 'payment-methods' ? [['code', 'Code'], ['method_type', 'Type']] : [['phone', 'Phone'], ['email', 'Email']]), ['active', 'Active']]} renderActions={canWrite ? row => row.method_type === 'exchange' ? <span className="muted">Managed by returns</span> : <RecordActions name={row.name} onEdit={() => setRecord(row)} onDelete={() => setDeleting(row)} deletingDisabled={!row.active} deleteHint={!row.active ? 'Already inactive. Edit this record to restore it.' : undefined} /> : undefined} revision={revision} />{record && <Modal key={`${resource}-${record.id || 'new'}`} title={<>{record.id ? 'Edit' : 'Add'} {config[0].toLowerCase()}</>} onClose={() => setRecord(null)}><SaveForm onSave={save} onCancel={() => setRecord(null)}><Input name="name" label="Name" value={record.name} />{['customers', 'suppliers'].includes(resource) && <><Input name="phone" label="Phone" value={record.phone} required={false} /><Input name="email" label="Email" type="email" value={record.email} required={false} /></>}{resource === 'customers' && <Input name="notes" label="Notes" value={record.notes} required={false} />} {resource === 'suppliers' && <><Input name="address" label="Address" value={record.address} required={false} /><Input name="payment_terms_days" label="Payment terms in days" type="number" min="0" max="365" value={record.payment_terms_days ?? 0} /></>}{resource === 'registers' && <><LookupSelect name="branch_id" label="Branch" endpoint="catalog/branches" options={refs.data['catalog/branches']?.data || []} value={record.branch_id} /><LookupSelect name="location_id" label="Store location" endpoint="catalog/locations" options={refs.data['catalog/locations']?.data || []} value={record.location_id} /></>}{resource === 'payment-methods' && <><Input name="code" label="Code" value={record.code} pattern={'[A-Z0-9_-]{1,40}'} /><label>Type<ValidatedControl><select name="method_type" defaultValue={record.method_type || 'cash'} aria-label={"Type"}><option value="cash">Cash</option><option value="card">External card terminal</option><option value="bank">Confirmed bank transfer</option></select></ValidatedControl></label></>}<label className="checkbox"><ValidatedControl><input type="checkbox" name="active" defaultChecked={record.active !== false && record.active !== 0} aria-label={"Active"} /></ValidatedControl>Active</label></SaveForm></Modal>}{deleting && <DeleteDialog name={deleting.name} confirmLabel="Deactivate record" description="This removes the record from active use and retains all transaction history. Restore it using the Active checkbox in Edit. Registers must have their shifts closed first." onClose={() => setDeleting(null)} onDelete={async () => { await workspaceApi.remove(`operations/${resource}/${deleting.id}`); setRevision(r => r + 1); }} />}</main>;
}
