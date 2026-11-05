import { useEffect, useState } from 'react';
import LoadingState from '../components/LoadingState';
import { Link } from 'react-router-dom';
import LookupSelect from '../components/LookupSelect';
import { ErrorNotice, FormDetails, FormSection, Input, SaveForm, Select, ValidatedControl } from '../components/WorkspaceForm';
import { workspaceApi } from '../api/workspace';
export function variantValues(form, prefix = '') {
  return {
    sku: form.get(`${prefix}sku`),
    name: form.get(`${prefix}name`) || null,
    base_unit_id: form.get(`${prefix}base_unit_id`),
    default_cost: form.get(`${prefix}default_cost`),
    default_price: form.get(`${prefix}default_price`),
    tracking_mode: form.get(`${prefix}tracking_mode`),
    active: form.has(`${prefix}active`)
  };
}
export function VariantFields({
  variant = {},
  units,
  prefix = '',
  title = 'Variant details'
}) {
  return <><FormSection title={title} description="A unique SKU and unit of measure identify this sellable item.">
    {units.length === 0 && <p>First <Link to="/setup/units">add a unit of measure</Link>, then return here.</p>}
    <div className="form-grid"><Input name={`${prefix}sku`} label="SKU" value={variant.sku} />
    <Input name={`${prefix}name`} label="Variant name" value={variant.name} required={false} />
    <LookupSelect endpoint="catalog/units" name={`${prefix}base_unit_id`} label="Unit of measure" value={variant.base_unit_id} options={units} />
    </div></FormSection>
    <FormSection title="Pricing" description="Catalog defaults before tax. Existing precision is preserved."><div className="form-grid">
    <Input name={`${prefix}default_cost`} label="Default cost" value={variant.default_cost ?? '0.0000'} inputMode="decimal" />
    <Input name={`${prefix}default_price`} label="Default price" value={variant.default_price ?? '0.0000'} inputMode="decimal" />
    </div></FormSection>
    <FormDetails title="Variant settings" description="Review tracking and availability when this variant needs different settings.">
    <Select name={`${prefix}tracking_mode`} label="Tracking" value={variant.tracking_mode ?? 'none'} options={['none', 'batch', 'serial']} />
    <label className="checkbox-label"><ValidatedControl><input name={`${prefix}active`} type="checkbox" defaultChecked={variant.active ?? true} aria-label={"Active variant"} /></ValidatedControl>Active variant</label>
    </FormDetails></>;
}
export function ProductForm({
  product = {},
  units,
  categories,
  brands,
  tax,
  onSaved,
  onCancel
}) {
  const creating = !product.id;
  async function save(form) {
    const data = {
      name: form.get('name'),
      description: form.get('description') || null,
      product_type: form.get('product_type'),
      track_inventory: form.has('track_inventory'),
      active: form.has('active'),
      category_id: form.get('category_id') || null,
      brand_id: form.get('brand_id') || null,
      tax_class_id: form.get('tax_class_id') || null
    };
    if (creating) data.variant = variantValues(form, 'variant_');
    const result = await workspaceApi.save(creating ? 'products' : `products/${product.id}`, data, !creating);
    await onSaved(result.data);
  }
  return <SaveForm onSave={save} onCancel={onCancel} label={creating ? 'Create product' : 'Save product'}>
    <FormSection title="Product details" description={creating ? 'Product name, SKU and unit of measure are required.' : 'Update the shared details for this product.'}>
    <div className="form-grid"><Input name="name" label="Product name" value={product.name} />
      <Select name="product_type" label="Product type" value={product.product_type || 'simple'} options={['simple', 'variable', 'service', 'digital']} />
      <LookupSelect endpoint="catalog/categories" name="category_id" label="Category" value={product.category_id} options={categories} required={false} />
      <LookupSelect endpoint="catalog/tax-classes" name="tax_class_id" label="Tax class" value={product.tax_class_id} options={tax} required={false} />
    </div></FormSection>
    {creating && <VariantFields units={units} prefix="variant_" title="First variant" />}
    <FormSection title="Inventory & availability" description="Choose whether to track stock and make the product available.">
    <div className="checks"><label><ValidatedControl><input name="track_inventory" type="checkbox" defaultChecked={product.track_inventory ?? true} aria-label={"Track inventory"} /></ValidatedControl>Track inventory</label>
      <label><ValidatedControl><input name="active" type="checkbox" defaultChecked={product.active ?? true} aria-label={"Active product"} /></ValidatedControl>Active product</label></div>
    </FormSection>
    <FormDetails title="Additional product details" description="Brand and description are optional. Existing values are kept when this section is closed.">
      <LookupSelect endpoint="catalog/brands" name="brand_id" label="Brand" value={product.brand_id} options={brands} required={false} />
      <label>Description<ValidatedControl><textarea name="description" defaultValue={product.description || ''} maxLength={10000} aria-label={"Description"} /></ValidatedControl></label>
    </FormDetails>
  </SaveForm>;
}
export function NewProductForm({
  onSaved,
  onCancel,
  productId
}) {
  const [state, setState] = useState({
    loading: true
  });
  useEffect(() => {
    let alive = true;
    Promise.all([...['units', 'categories', 'brands', 'tax-classes'].map(resource => workspaceApi.get(`catalog/${resource}`)), ...(productId ? [workspaceApi.get(`products/${productId}`)] : [])]).then(([units, categories, brands, tax, product]) => {
      if (alive) setState({
        units: units.data,
        categories: categories.data,
        brands: brands.data,
        tax: tax.data,
        product: product?.data
      });
    }).catch(error => {
      if (alive) setState({
        error
      });
    });
    return () => {
      alive = false;
    };
  }, [productId]);
  return <><LoadingState busy={Boolean(state.loading)} label="Loading product options…" variant={state.units ? 'inline' : 'form'} />
    {state.error && <ErrorNotice error={state.error} />}
    {!state.loading && !state.error && <ProductForm {...state} onSaved={onSaved} onCancel={onCancel} />}
  </>;
}
