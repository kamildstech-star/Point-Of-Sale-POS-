import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { authApi } from '../../api/client';
import Modal from '../Modal';
import Icon from '../Icon';
import LoadingState from '../LoadingState';
import { ErrorNotice, SaveForm, Select } from '../WorkspaceForm';

function Memberships({ onClose }) {
  const { user, changeBusiness } = useAuth();
  const [revision, setRevision] = useState(0);
  const [state, setState] = useState({ loading: true });
  useEffect(() => {
    let alive = true;
    setState({ loading: true });
    authApi.tenants().then(result => { if (alive) setState({ rows: result.data }); })
      .catch(error => { if (alive) setState({ error }); });
    return () => { alive = false; };
  }, [revision]);
  async function activate(form) {
    const id = Number(form.get('business'));
    if (!state.rows?.some(tenant => String(tenant.id) === String(id))) throw new Error('Choose one of your business memberships.');
    if (id !== user.active_tenant?.id) await changeBusiness(id);
  }
  return <>
    <p className="muted">Choose a business you belong to. Your business access is checked by the server.</p>
    <LoadingState busy={Boolean(state.loading)} label="Loading business memberships…" variant="form" rows={2}/>
    <ErrorNotice error={state.error} onRetry={() => setRevision(value => value + 1)}/>
    {state.rows?.length > 0 && <SaveForm onSave={activate} label="Switch business" onCancel={onClose}>
      <Select name="business" label="Business" value={user.active_tenant?.id} options={state.rows}/>
    </SaveForm>}
    {state.rows?.length === 0 && <p>No business memberships are available.</p>}
    <p><Link to="/workspace" onClick={onClose}>Manage your businesses</Link></p>
  </>;
}

export default function TenantSwitcher({ variant = 'topbar', collapsed = false }) {
  const { user } = useAuth();
  const [open, setOpen] = useState(false);
  return <>
    <button type="button" className={`secondary tenant-switcher${variant === 'sidebar' ? ' tenant-switcher--sidebar' : ''}${collapsed ? ' tenant-switcher--collapsed' : ''}`} aria-label="Choose business" aria-haspopup="dialog" onClick={() => setOpen(true)}
      title={user.active_tenant?.name || 'Choose your business'}>
      <Icon name="businesses"/><span className="tenant-switcher__copy"><strong>{user.active_tenant?.name || 'Choose business'}</strong>{variant === 'sidebar' && <small>Business workspace</small>}</span><Icon name="down" size={16}/>
    </button>
    {open && <Modal title="Switch business" onClose={() => setOpen(false)} onSubmitSuccess={() => setOpen(false)}>
      <Memberships onClose={() => setOpen(false)}/>
    </Modal>}
  </>;
}
