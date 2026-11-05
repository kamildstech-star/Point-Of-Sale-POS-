import { columnClass } from '../components/ui/tableColumns';
import TableViewport from '../components/ui/TableViewport';
import { ListEmptyState, ListPagination } from '../components/ui/ListPatterns';
import Icon from '../components/Icon';
import LoadingState from '../components/LoadingState';
import Modal from "../components/Modal";
import { useEffect, useRef, useState } from 'react';
import LookupSelect from '../components/LookupSelect';
import { NavLink, useParams } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice, Input, SaveForm, Select, ValidatedControl } from '../components/WorkspaceForm';
import { references, referenceRemoval, referenceIcons } from './catalogReferences';
import CategoryFields from './CategoryFields';
import RecordActions, { DeleteDialog } from '../components/RecordActions';
export default function ReferencePage() {
  const { resource = 'categories' } = useParams();
  return <ReferenceRecords key={resource} resource={resource} />;
}
function ReferenceRecords({ resource }) {
  const {
    user
  } = useAuth();
  const config = references[resource];
  const [state, setState] = useState({
    loading: true
  });
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [page, setPage] = useState(1);
  const generation = useRef(0);
  async function load() {
    const current = ++generation.current;
    setState(previous => ({ ...previous, loading: true, error: null }));
    try {
      const relationNames = [...new Set(config.fields.filter(field => field[2] === 'relation').map(field => field[3]))];
      const [rows, ...relations] = await Promise.all([workspaceApi.get(`catalog/${resource}?page=${page}`), ...relationNames.map(name => workspaceApi.get(name === 'variants' ? 'variants' : `catalog/${name}`))]);
      if (current !== generation.current) return;
      if (page > rows.last_page) { setPage(rows.last_page); return; }
      setState({
        rows,
        options: Object.fromEntries(relationNames.map((name, index) => [name, relations[index].data]))
      });
    } catch (error) {
      if (current === generation.current) setState(previous => ({ ...previous, loading: false, error }));
    }
  }
  useEffect(() => {
    if (config) load();
    setEditing(null);
    setDeleting(null);
    return () => { generation.current++; };
  }, [resource, page]);
  if (!config) return <main className="content"><h1>Setup page not found</h1></main>;
  const canManage = user.permissions.includes(['branches', 'locations'].includes(resource) ? 'settings.manage' : 'products.manage');
  return <main className="content"><p className="eyebrow">CATALOG & BUSINESS SETUP</p><h1>{config.label}</h1><div className="setup-tabs">{Object.entries(references).filter(([key]) => !['branches', 'locations'].includes(key) || user.permissions.includes('settings.manage')).map(([key, value]) => <NavLink key={key} to={`/setup/${key}`}><Icon name={referenceIcons[key]} />{value.label}</NavLink>)}</div><ErrorNotice error={state.error} onRetry={load} /><LoadingState busy={Boolean(state.loading)} label={`Loading ${config.label.toLowerCase()}…`} variant={state.rows ? 'inline' : 'table'} columns={config.fields.slice(0, 4).length + (canManage ? 1 : 0)} />{state.rows && <><div className="toolbar"><span className="muted">{config.label}</span>{canManage && <button onClick={() => setEditing({})}><Icon name="add" />Add {config.label.toLowerCase()}</button>}</div><TableViewport label={config.label} className="table-wrap card" stickyHeader aria-busy={Boolean(state.loading)} inert={state.loading || state.error ? '' : undefined}><table><thead><tr>{config.fields.slice(0, 4).map(field => <th key={field[0]} scope="col" className={columnClass(field[0])}>{field[1]}</th>)}{canManage && <th scope="col" className="table-actions">Actions</th>}</tr></thead><tbody>{state.rows.data.map(row => <tr key={row.id}>{config.fields.slice(0, 4).map(([name,, type, source]) => <td key={name} className={columnClass(name)}>{type === 'checkbox' ? row[name] ? 'Yes' : 'No' : type === 'relation' ? state.options[source]?.find(option => option.id === row[name])?.name || state.options[source]?.find(option => option.id === row[name])?.sku || state.options[source]?.find(option => option.id === row[name])?.value || '—' : row[name]}</td>)}{canManage && <td className="table-actions"><RecordActions name={row.name || row.value || row.barcode || `${config.label} #${row.id}`} onEdit={() => setEditing(row)} onDelete={() => setDeleting(row)} deletingDisabled={referenceRemoval(resource, row).disabled} deleteHint={referenceRemoval(resource, row).hint} /></td>}</tr>)}</tbody></table></TableViewport>{!state.loading && !state.error && state.rows.data.length === 0 && <ListEmptyState description={`No ${config.label.toLowerCase()} have been added yet.`} />}<ListPagination label={config.label} data={state.rows} busy={Boolean(state.loading)} failed={Boolean(state.error)} onPageChange={setPage} />{editing && <Modal key={`${resource}-${editing.id || 'new'}`} title={<>{editing.id ? 'Edit record' : 'Add record'}</>} onClose={() => setEditing(null)}><SaveForm onCancel={() => setEditing(null)} onSave={async form => {
          const values = Object.fromEntries(form);
          for (const [name,, type] of config.fields) {
            if (type === 'checkbox') values[name] = form.has(name);else if (values[name] === '') values[name] = null;
          }
          await workspaceApi.save(`catalog/${resource}${editing.id ? `/${editing.id}` : ''}`, values, Boolean(editing.id));
          setEditing(null);
          await load();
        }}>{resource === 'categories' ? <CategoryFields category={editing} categories={state.options.categories || []} /> : <>{config.fields.map(([name, label, type = 'text', source, optional]) => type === 'checkbox' ? <label className="checkbox-label" key={name}><ValidatedControl><input type="checkbox" name={name} defaultChecked={editing[name] ?? true} /></ValidatedControl>{label}</label> : type === 'relation' ? <LookupSelect key={name} name={name} label={label} value={editing[name]} endpoint={source === 'variants' ? 'variants' : 'catalog/' + source} options={state.options[source]} required={!optional} /> : type === 'choice' ? <Select key={name} name={name} label={label} value={editing[name]} options={type === 'relation' ? state.options[source] : source} required={!optional} /> : <Input key={name} name={name} label={label} type={type} value={editing[name] ?? (name === 'quantity_precision' || name === 'sort_order' ? '0' : name === 'currency_code' ? user.active_tenant.currency_code : name === 'min_qty' ? '1' : '')} required={!optional} />)}</>}</SaveForm></Modal>}</>}{deleting && <DeleteDialog name={deleting.name || deleting.value || deleting.barcode || `${config.label} #${deleting.id}`} {...referenceRemoval(resource, deleting)} onClose={() => setDeleting(null)} onDelete={async () => { await workspaceApi.remove(`catalog/${resource}/${deleting.id}`); await load(); }} />}</main>;
}
