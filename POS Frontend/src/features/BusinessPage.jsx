import Icon from '../components/Icon';
import LoadingState from '../components/LoadingState';
import Modal from "../components/Modal";
import { useEffect, useState } from 'react';
import { useAuth } from '../auth/AuthProvider';
import { authApi } from '../api/client';
import { ErrorNotice, Input, SaveForm } from '../components/WorkspaceForm';
export function BusinessFields({
  registration = false
}) {
  return <><Input name={registration ? 'business_name' : 'name'} label="Business name" /><Input name="slug" label="Business address name" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="my-retail-shop" maxLength={100} /><Input name="currency_code" label="Currency code" value="PKR" pattern="[A-Z]{3}" maxLength={3} /><Input name="timezone" label="Time zone" value="Asia/Karachi" /></>;
}
export default function BusinessPage() {
  const {
    user,
    changeBusiness,
    createBusiness
  } = useAuth();
  const [state, setState] = useState({
    loading: true
  });
  const [creating, setCreating] = useState(false);
  const [busy, setBusy] = useState(false);
  async function load() {
    setState(previous => ({ ...previous, loading: true, error: null }));
    try {
      setState({
        data: (await authApi.tenants()).data
      });
    } catch (error) {
      setState(previous => ({ ...previous, loading: false, error }));
    }
  }
  useEffect(() => {
    load();
  }, [user.active_tenant?.id]);
  async function activate(id) {
    setBusy(true);
    try {
      await changeBusiness(id);
    } catch (error) {
      setState(previous => ({
        ...previous,
        error
      }));
    } finally {
      setBusy(false);
    }
  }
  return <main className="content"><p className="eyebrow">BUSINESSES</p><h1>Your business workspace</h1><p className="muted">Switch between businesses you belong to, or create a new one.</p><ErrorNotice error={state.error} onRetry={load} /><LoadingState busy={Boolean(state.loading)} label="Loading businesses…" variant={state.data ? 'inline' : 'cards'} rows={3} /><div className="business-grid" aria-busy={Boolean(state.loading)}>{state.data?.map(tenant => <article className="card" key={tenant.id}><span className="tile-icon"><Icon name="businesses" /></span><h2>{tenant.name}</h2><p>{tenant.currency_code} · {tenant.status}</p><button className={tenant.id === user.active_tenant?.id ? 'secondary' : ''} disabled={busy || tenant.id === user.active_tenant?.id} onClick={() => activate(tenant.id)}><Icon name="view" />{tenant.id === user.active_tenant?.id ? 'Active business' : 'Open business'}</button></article>)}</div>{state.data?.length === 0 && <p>No business memberships are available. Create your first business below.</p>}<section className="card editor-card"><h2>{creating ? 'Create a business' : 'Ready for another business?'}</h2>{creating ? <Modal title={"Create a business"} onClose={() => setCreating(false)}><SaveForm label="Create business" onCancel={() => setCreating(false)} onSave={async form => {
          await createBusiness(Object.fromEntries(form));
          setCreating(false);
        }}><BusinessFields /></SaveForm></Modal> : <button onClick={() => setCreating(true)}><Icon name="add" />Create business</button>}</section></main>;
}
