export const references = {
  categories: { label: 'Categories', fields: [['name', 'Name'], ['slug', 'Category slug'], ['parent_id', 'Parent category', 'relation', 'categories', true], ['active', 'Active', 'checkbox']] },
  brands: { label: 'Brands', fields: [['name', 'Name'], ['code', 'Code'], ['active', 'Active', 'checkbox']] },
  units: { label: 'Units', fields: [['name', 'Name'], ['code', 'Code'], ['quantity_precision', 'Decimal places', 'number'], ['active', 'Active', 'checkbox']] },
  attributes: { label: 'Attributes', fields: [['name', 'Name'], ['code', 'Code'], ['active', 'Active', 'checkbox']] },
  'attribute-values': { label: 'Attribute values', fields: [['attribute_id', 'Attribute', 'relation', 'attributes'], ['value', 'Value'], ['sort_order', 'Display order', 'number']] },
  'tax-classes': { label: 'Tax classes', fields: [['name', 'Name'], ['code', 'Code'], ['active', 'Active', 'checkbox']] },
  'tax-rates': { label: 'Tax rates', fields: [['tax_class_id', 'Tax class', 'relation', 'tax-classes'], ['jurisdiction_code', 'Jurisdiction'], ['rate_percent', 'Rate (%)'], ['valid_from', 'Effective from', 'date'], ['valid_to', 'Effective until', 'date', null, true]] },
  'price-lists': { label: 'Price lists', fields: [['name', 'Name'], ['currency_code', 'Currency code'], ['active', 'Active', 'checkbox']] },
  'price-list-items': { label: 'Price list items', fields: [['price_list_id', 'Price list', 'relation', 'price-lists'], ['variant_id', 'Variant', 'relation', 'variants'], ['unit_price', 'Unit price'], ['min_qty', 'Minimum quantity']] },
  barcodes: { label: 'Barcodes', fields: [['variant_id', 'Variant', 'relation', 'variants'], ['barcode', 'Barcode'], ['symbology', 'Barcode format', 'text', null, true]] },
  'variant-values': { label: 'Variant attributes', fields: [['variant_id', 'Variant', 'relation', 'variants'], ['attribute_id', 'Attribute', 'relation', 'attributes'], ['value_id', 'Attribute value', 'relation', 'attribute-values']] },
  branches: { label: 'Branches', fields: [['name', 'Name'], ['code', 'Code'], ['address', 'Address', 'text', null, true], ['phone', 'Phone', 'text', null, true], ['status', 'Status', 'choice', ['active', 'inactive']]] },
  locations: { label: 'Stock locations', fields: [['name', 'Name'], ['code', 'Code'], ['branch_id', 'Branch', 'relation', 'branches', true], ['type', 'Location type', 'choice', ['store', 'warehouse', 'quarantine', 'in_transit']], ['status', 'Status', 'choice', ['active', 'inactive']]] },
};
const archivedReferences = ['categories', 'brands', 'units', 'attributes', 'tax-classes', 'price-lists'];
export const referenceIcons = {
  categories: 'folder', brands: 'tag', units: 'grid', attributes: 'filter',
  'attribute-values': 'list', 'tax-classes': 'refund', 'tax-rates': 'reports',
  'price-lists': 'list', 'price-list-items': 'billing', barcodes: 'barcode',
  'variant-values': 'product', branches: 'branch', locations: 'store',
};
export function referenceRemoval(resource, record) {
  if (resource === 'tax-rates') return { disabled: true, hint: 'Tax rates are retained for history. Edit the effective end date instead.' };
  if (archivedReferences.includes(resource)) return {
    disabled: record.active === false || record.active === 0,
    confirmLabel: 'Archive record',
    description: 'This archives the record and retains its relationships and history. Restore it using the Active checkbox in Edit.',
  };
  if (['branches', 'locations'].includes(resource)) return {
    disabled: record.status === 'inactive',
    confirmLabel: 'Deactivate record',
    description: 'This deactivates the record and retains its history. Close register shifts and clear stock and reservations first. Restore it using Status in Edit.',
  };
  return { confirmLabel: 'Delete record', description: 'This permanently removes this catalog entry. Posted transaction snapshots are retained. An attribute value assigned to a variant must be unassigned first.' };
}
