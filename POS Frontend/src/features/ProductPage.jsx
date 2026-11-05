import TableViewport from '../components/ui/TableViewport';
import Icon from '../components/Icon';
import LoadingState from '../components/LoadingState';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider';
import { workspaceApi } from '../api/workspace';
import { ErrorNotice, Input, SaveForm } from '../components/WorkspaceForm';
import Modal from '../components/Modal';
import CatalogPage from './CatalogPage';
import { ProductForm, VariantFields, variantValues } from './ProductForms';
import RecordActions, { ActionButton, DeleteDialog } from '../components/RecordActions';

export default function ProductPage() {
  const { productId } = useParams();
  return productId ? <ProductDetails key={productId} productId={productId}/> : <CatalogPage initialCreate/>;
}

function ProductDetails({ productId }) {
  const { user } = useAuth(); const navigate = useNavigate();
  const [state, setState] = useState({ loading: true });
  const [editor, setEditor] = useState(null); const [variant, setVariant] = useState(null);
  const [deletingVariant, setDeletingVariant] = useState(null);
  const manage = user.permissions.includes('products.manage');
  const viewCosts = ['products.manage', 'purchases.view', 'reports.view'].some(permission => user.permissions.includes(permission));
  async function load() {
    setState(previous => ({ ...previous, loading: true, error: null }));
    try {
      const [units, categories, brands, tax, product] = await Promise.all([
        workspaceApi.get('catalog/units'), workspaceApi.get('catalog/categories'), workspaceApi.get('catalog/brands'),
        workspaceApi.get('catalog/tax-classes'), workspaceApi.get(`products/${productId}`),
      ]);
      setState({ units: units.data, categories: categories.data, brands: brands.data, tax: tax.data, product: product.data });
    } catch (error) { setState(previous => ({ ...previous, loading: false, error })); }
  }
  useEffect(() => { load(); }, [productId]);
  const product = state.product;
  return <main className="content"><Link to="/catalog"><Icon name="back" /> Catalog</Link>
    <div className="page-heading"><h1>{product?.name || 'Product'}</h1>{product && manage && <ActionButton action="edit" label="Edit product" onClick={() => setEditor('product')} />}</div>
    <ErrorNotice error={state.error} onRetry={load}/><LoadingState busy={Boolean(state.loading)} label="Loading product…" variant={product ? 'inline' : 'form'} />
    {product && <><section className="card" aria-busy={Boolean(state.loading)}><dl className="product-details">
      <div><dt>Product type</dt><dd>{product.product_type}</dd></div><div><dt>Status</dt><dd>{product.active ? 'Active' : 'Archived'}</dd></div>
      <div><dt>Inventory</dt><dd>{product.track_inventory ? 'Tracked' : 'Untracked'}</dd></div>
      <div><dt>Category</dt><dd>{state.categories.find(row => row.id === product.category_id)?.name || 'None'}</dd></div>
      <div><dt>Brand</dt><dd>{state.brands.find(row => row.id === product.brand_id)?.name || 'None'}</dd></div>
      <div><dt>Description</dt><dd>{product.description || '—'}</dd></div>
    </dl></section>
    <section className="card editor-card"><div className="toolbar"><h2>Variants</h2>
      {manage && product.product_type === 'variable' && <button onClick={() => setVariant({})}><Icon name="add" />Add variant</button>}</div>
      <TableViewport className="table-wrap"><table><thead><tr><th>SKU</th><th>Name</th>{viewCosts && <th className="numeric">Cost</th>}<th className="numeric">Price</th><th>Status</th>{manage && <th>Actions</th>}</tr></thead>
        <tbody>{(product.variants || []).map(row => <tr key={row.id}><td>{row.sku}</td><td>{row.name || 'Default'}</td>{viewCosts && <td className="numeric">{row.default_cost}</td>}
          <td className="numeric">{row.default_price} {user.active_tenant.currency_code}</td><td>{row.active ? 'Active' : 'Archived'}</td>
          {manage && <td><RecordActions name={row.sku} editLabel="Edit variant" deleteLabel="Delete variant" onEdit={() => setVariant(row)} onDelete={() => setDeletingVariant(row)} deletingDisabled={!row.active} /></td>}</tr>)}</tbody></table></TableViewport>
      <p className="footnote">Catalog defaults are verified again when the POS calculates a sale.</p></section>
    <section className="card editor-card"><div className="toolbar"><h2>Product images</h2>{manage && <button onClick={() => setEditor('image')}><Icon name="upload" />Upload image</button>}</div>
      {(product.images || []).map(image => <p key={image.id}><a href={workspaceApi.image(image.id)}>{image.original_name}</a></p>)}</section>
    {manage && <section className="card editor-card"><h2>Archive product</h2><p>Archive this product and its variants while retaining references and history.</p>
      <ActionButton action="delete" label="Archive product" disabled={!product.active} onClick={() => setEditor('archive')} /></section>}
    {editor === 'product' && <Modal size="large" title="Edit product" onClose={() => setEditor(null)}><ProductForm {...state} onCancel={() => setEditor(null)} onSaved={async () => { await load(); setEditor(null); }}/></Modal>}
    {variant && <Modal size="large" title={variant.id ? 'Edit variant' : 'Add variant'} onClose={() => setVariant(null)}>
      <SaveForm onCancel={() => setVariant(null)} onSave={async form => { await workspaceApi.save(`products/${productId}/variants${variant.id ? `/${variant.id}` : ''}`, variantValues(form), Boolean(variant.id)); await load(); setVariant(null); }}>
        <VariantFields variant={variant} units={state.units}/></SaveForm></Modal>}
    {editor === 'image' && <Modal title="Upload product image" size="small" onClose={() => setEditor(null)}>
      <SaveForm label="Upload image" onSave={async form => { await workspaceApi.save(`products/${productId}/images`, form); await load(); setEditor(null); }}>
        <Input name="image" label="Image" type="file" accept="image/png,image/jpeg,image/webp"/></SaveForm></Modal>}
    {editor === 'archive' && <DeleteDialog title="Archive product" name={product.name} confirmLabel="Confirm archive" description="This archives the product and all variants while retaining stock and transaction history." onClose={() => setEditor(null)} onDelete={async () => { await workspaceApi.remove(`products/${productId}`); navigate('/catalog'); }} />}
    {deletingVariant && <DeleteDialog title="Delete variant" name={deletingVariant.sku} confirmLabel="Archive variant" description="This archives the variant and retains its stock and transaction history. Restore it using the Active variant checkbox in Edit." onClose={() => setDeletingVariant(null)} onDelete={async () => { await workspaceApi.remove(`products/${productId}/variants/${deletingVariant.id}`); await load(); }} />}
    </>}
  </main>;
}
