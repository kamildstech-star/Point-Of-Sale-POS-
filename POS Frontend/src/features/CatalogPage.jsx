import TableViewport from '../components/ui/TableViewport';
import { AppliedFilters, ListEmptyState, ListPagination } from '../components/ui/ListPatterns';
import Icon from '../components/Icon';
import LoadingState from '../components/LoadingState';
import { lazy, Suspense, useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice, SaveForm, ValidatedControl } from '../components/WorkspaceForm';
import Modal from '../components/Modal';
import RecordActions, { DeleteDialog } from '../components/RecordActions';
const NewProductForm = lazy(() => import('./ProductForms').then(module => ({ default: module.NewProductForm })));

export default function CatalogPage({ initialCreate = false }) {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [editing, setEditing] = useState(initialCreate ? {} : null);
  const [deleting, setDeleting] = useState(null);
  const [state, setState] = useState({ loading: true });
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState('');
  const [exporting, setExporting] = useState(false);
  const generation = useRef(0);
  const manage = user.permissions.includes('products.manage');
  function closeEditor() {
    setEditing(null);
    if (initialCreate) navigate('/catalog', { replace: true });
  }
  async function load() {
    const current = ++generation.current;
    setState(previous => ({ ...previous, result: previous.filter === filter ? previous.result : undefined, loading: true, error: null }));
    try {
      const result = await workspaceApi.get(`products?page=${page}&search=${encodeURIComponent(filter)}`);
      if (current !== generation.current) return;
      if (page > result.last_page) { setPage(result.last_page); return; }
      setState({ result, filter });
    } catch (error) {
      if (current === generation.current) setState(previous => ({ ...previous, loading: false, error }));
    }
  }
  useEffect(() => { load(); return () => { generation.current++; }; }, [page, filter]);
  function clearSearch() { setSearch(''); setFilter(''); setPage(1); }
  return <main className="content"><p className="eyebrow">CATALOG</p><h1>Your products</h1>
    <p className="muted">Products, variants and prices for {user.active_tenant.name}.</p>
    <div className="toolbar"><SaveForm className="search-form" label="Search" onSave={form => { setPage(1); setFilter(form.get('search')); }}>
      <ValidatedControl><input name="search" aria-label="Search products" placeholder="Search by product name" value={search} onChange={event => setSearch(event.target.value)} maxLength={100} /></ValidatedControl>
    </SaveForm><div className="form-actions"><button className="secondary" disabled={exporting} onClick={async () => {
      setExporting(true);
      try { await workspaceApi.exportCatalog(); }
      catch (error) { setState(previous => ({ ...previous, error })); }
      finally { setExporting(false); }
    }}><Icon name="download" />Export CSV</button>{manage && <button onClick={() => setEditing({})}><Icon name="add" />Add product</button>}</div></div>
    <AppliedFilters filters={filter ? [{ label: 'Search', value: filter }] : []} onClear={clearSearch} />
    <ErrorNotice error={state.error} onRetry={load} /><LoadingState busy={Boolean(state.loading)} label="Loading catalog…" variant={state.result ? 'inline' : 'table'} columns={5} />
    {state.result && <><TableViewport label="Products" className="table-wrap card" stickyHeader aria-busy={Boolean(state.loading)} inert={state.loading || state.error ? '' : undefined}><table><thead><tr><th scope="col">Product</th><th scope="col">Type</th><th scope="col">Inventory</th><th scope="col">Status</th><th scope="col" className="table-actions">Actions</th></tr></thead>
      <tbody>{state.result.data.map(product => <tr key={product.id}><td><strong>{product.name}</strong></td><td>{product.product_type}</td>
        <td>{product.track_inventory ? 'Tracked' : 'Untracked'}</td><td>{product.active ? 'Active' : 'Archived'}</td><td className="table-actions">
          <Link to={`/catalog/${product.id}`}>Open product <Icon name="forward" /></Link>{manage && <RecordActions name={product.name} editLabel="Edit product" deleteLabel="Delete product"
            onEdit={() => setEditing(product)} onDelete={() => setDeleting(product)} deletingDisabled={!product.active} deleteHint={!product.active ? 'This product is already archived. Edit it to restore access.' : undefined} />}
        </td></tr>)}</tbody></table>
    </TableViewport>{!state.loading && !state.error && state.result.data.length === 0 && <ListEmptyState filtered={Boolean(filter)} title={filter ? 'No matching products' : 'Your catalog starts here'} description={filter ? 'Try a different product name or clear the search to see your catalog.' : 'Add your first product, or review legacy mappings if this is your existing business.'}>
      {filter ? <button type="button" className="secondary" onClick={clearSearch}>Clear search</button> : manage && <Link to="/setup/units">Set up units of measure</Link>}
    </ListEmptyState>}<ListPagination label="Products" data={state.result} busy={Boolean(state.loading)} failed={Boolean(state.error)} onPageChange={setPage} /></>}
    {editing && <Modal key={editing.id || 'new'} size="large" title={editing.id ? 'Edit product' : 'Add product'} onClose={closeEditor}>
      <Suspense fallback={<LoadingState label="Loading product form…" variant="form" />}><NewProductForm productId={editing.id} onCancel={closeEditor} onSaved={async () => {
        const wasNew = !editing.id;
        closeEditor();
        if (wasNew && page !== 1) setPage(1); else await load();
      }} /></Suspense>
    </Modal>}
    {deleting && <DeleteDialog title="Delete product" name={deleting.name} confirmLabel="Archive product"
      description="This archives the product and all its variants. Stock, sales and transaction history are retained. You can restore the product and its variants using Edit."
      onClose={() => setDeleting(null)} onDelete={async () => { await workspaceApi.remove(`products/${deleting.id}`); await load(); }} />}
  </main>;
}
