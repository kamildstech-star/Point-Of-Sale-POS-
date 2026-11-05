import TableViewport from '../components/ui/TableViewport';
import Icon from '../components/Icon';
import LoadingState from '../components/LoadingState';
import Modal from "../components/Modal";
import { useEffect, useState } from 'react';
import { useAuth } from '../auth/AuthProvider';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice, Input, SaveForm, Select, ValidatedControl } from '../components/WorkspaceForm';
import RecordActions, { DeleteDialog } from '../components/RecordActions';
export default function TeamPage() {
  const {
    user,
    refresh
  } = useAuth();
  const [state, setState] = useState({
    loading: true
  });
  const [editing, setEditing] = useState(null);
  const [custom, setCustom] = useState(false);
  const [deleting, setDeleting] = useState(null);
  async function load() {
    setState(previous => ({ ...previous, loading: true, error: null }));
    try {
      const [team, roles] = await Promise.all([workspaceApi.get('team'), workspaceApi.get('roles')]);
      setState({
        team: team.data,
        roles: roles.data,
        codes: roles.meta.permission_codes
      });
    } catch (error) {
      setState(previous => ({ ...previous, loading: false, error }));
    }
  }
  useEffect(() => {
    load();
  }, []);
  const manage = user.permissions.includes('team.manage');
  return <main className="content"><p className="eyebrow">PEOPLE & ACCESS</p><h1>Your team</h1><p className="muted">Access is assigned separately for each business.</p><ErrorNotice error={state.error} onRetry={load} /><LoadingState busy={Boolean(state.loading)} label="Loading team…" variant={state.team ? 'inline' : 'table'} columns={manage ? 5 : 4} />{state.team && <><TableViewport className="table-wrap card" aria-busy={Boolean(state.loading)}><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th>{manage && <th>Actions</th>}</tr></thead><tbody>{state.team.map(member => <tr key={member.id}><td>{member.name}</td><td>{member.email}</td><td>{member.role}</td><td>{member.status}</td>{manage && <td><RecordActions name={member.name} editLabel="Edit access" deleteLabel="Remove access" onEdit={() => setEditing(member)} onDelete={() => setDeleting({ type: 'member', ...member })} deletingDisabled={member.status !== 'active'} /></td>}</tr>)}</tbody></table></TableViewport>{manage && <div className="form-actions"><button onClick={() => setEditing({})}><Icon name="add" />Add team member</button><button className="secondary" onClick={() => setCustom({})}><Icon name="add" />Create custom role</button></div>}{editing && <Modal key={editing.id || 'new'} title={<>{editing.id ? `Access for ${editing.name}` : 'Add a team member'}</>} onClose={() => setEditing(null)}><SaveForm onCancel={() => setEditing(null)} onSave={async form => {
          const data = Object.fromEntries(form);
          await workspaceApi.save(editing.id ? `team/${editing.id}` : 'team', data, Boolean(editing.id));
          setEditing(null);
          await load();
          if (editing.name === user.name) await refresh();
        }}>{!editing.id && <><Input name="name" label="Name" /><Input name="email" label="Email address" type="email" /><Input name="password" label="Initial password (new accounts only)" type="password" minLength={12} required={false} autoComplete="new-password" /></>}<Select name="role_id" label="Role" value={editing.role_id} options={state.roles} />{editing.id && <Select name="status" label="Status" value={editing.status} options={['active', 'suspended']} />}</SaveForm></Modal>}{custom && <Modal title={<>{custom.id ? 'Edit custom role' : 'Create custom role'}</>} onClose={() => setCustom(false)}><SaveForm key={custom.id || 'new-role'} onCancel={() => setCustom(false)} onSave={async form => {
          await workspaceApi.save(custom.id ? 'roles/' + custom.id : 'roles', {
            name: form.get('name'),
            code: form.get('code'),
            permissions: form.getAll('permissions')
          }, Boolean(custom.id));
          setCustom(false);
          await load();
        }}><Input name="name" label="Role name" value={custom.name} /><Input name="code" label="Role code" value={custom.code} placeholder="custom-store-assistant" pattern="custom-[a-z0-9-]+" maxLength={80} /><div className="checks">{state.codes.filter(code => user.permissions.includes(code)).map(code => <label key={code}><ValidatedControl><input type="checkbox" name="permissions" value={code} defaultChecked={custom.permissions?.includes(code) || false} /></ValidatedControl>{code.replaceAll('.', ' ').replaceAll('_', ' ')}</label>)}</div></SaveForm></Modal>}<section className="card editor-card"><h2>Business roles</h2>{state.roles.map(role => <p key={role.id}><strong>{role.name}</strong> <span className="muted">{role.permissions.join(', ') || 'No permissions'}</span>{manage && !role.is_system && <RecordActions name={role.name} editLabel="Edit role" deleteLabel="Delete role" onEdit={() => setCustom(role)} onDelete={() => setDeleting({ type: 'role', ...role })} />}</p>)}</section></>}{deleting && <DeleteDialog title={deleting.type === 'member' ? 'Remove team access' : 'Delete role'} question={deleting.type === 'member' ? 'Remove access for' : 'Delete'} name={deleting.name} confirmLabel={deleting.type === 'member' ? 'Remove access' : 'Delete role'} description={deleting.type === 'member' ? 'This suspends access to this business. The shared account, memberships in other businesses and transaction history are retained. Restore access using Edit access.' : 'This permanently deletes this custom role. Reassign all members, including suspended members, first.'} onClose={() => setDeleting(null)} onDelete={async () => { await workspaceApi.remove(`${deleting.type === 'member' ? 'team' : 'roles'}/${deleting.id}`); await load(); if (deleting.type === 'member') await refresh(); }} />}</main>;
}
