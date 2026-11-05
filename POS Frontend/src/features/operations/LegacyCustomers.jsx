import Icon from '../../components/Icon';
import Modal from "../../components/Modal";
import { useState } from 'react';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, SaveForm } from '../../components/WorkspaceForm';
import LookupSelect from '../../components/LookupSelect';
import { RemoteTable, useResources } from './OperationUI';
export default function LegacyCustomers({
  manage
}) {
  const [revision, setRevision] = useState(0);
  const [selected, setSelected] = useState(null);
  const [error, setError] = useState(null);
  const refs = useResources(manage ? ['operations/customers'] : []);
  async function map() {
    setError(null);
    try {
      await workspaceApi.save('legacy/map-customers', {});
      setRevision(r => r + 1);
    } catch (failure) {
      setError(failure);
    }
  }
  async function resolve(form) {
    await workspaceApi.save(`legacy/customer-links/${selected.id}`, {
      customer_id: Number(form.get('customer_id'))
    }, true);
    setSelected(null);
    setRevision(r => r + 1);
  }
  return <section className="card"><h2>Legacy retail customers</h2><p>Orders keep their original user IDs. Staff identities and missing users require owner review; mapping does not alter login credentials or create payment history.</p><ErrorNotice error={error || refs.error} />{manage && <button className="secondary" onClick={map}><Icon name="refresh" />Reconcile customer links</button>}<RemoteTable path="legacy/customer-links" columns={[["legacy_order_id", "Legacy order"], ["legacy_user_id", "Original user"], ["customer_id", "Retail customer"], ["status", "Mapping status"]]} onSelect={manage ? setSelected : undefined} revision={revision} />{selected && <Modal title={"Review customer link"} onClose={() => setSelected(null)}><SaveForm key={selected.id} label="Save reviewed customer link" onSave={resolve} onCancel={() => setSelected(null)}><p>Review legacy order #{selected.legacy_order_id} before choosing its retail customer.</p><LookupSelect name="customer_id" label="Retail customer" endpoint="operations/customers" options={refs.data['operations/customers']?.data || []} value={selected.customer_id} /></SaveForm></Modal>}</section>;
}
