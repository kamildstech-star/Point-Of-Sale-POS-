const resources = {
  products: 'Product', team: 'Team member', roles: 'Role',
  'catalog/categories': 'Category', 'catalog/brands': 'Brand', 'catalog/units': 'Unit',
  'catalog/attributes': 'Attribute', 'catalog/attribute-values': 'Attribute value',
  'catalog/tax-classes': 'Tax class', 'catalog/tax-rates': 'Tax rate',
  'catalog/price-lists': 'Price list', 'catalog/price-list-items': 'Price list item',
  'catalog/barcodes': 'Barcode', 'catalog/variant-values': 'Variant attribute',
  'catalog/branches': 'Branch', 'catalog/locations': 'Stock location',
  'operations/customers': 'Customer', 'operations/suppliers': 'Supplier',
  'operations/registers': 'Register', 'operations/payment-methods': 'Payment method',
};
export function apiPath(url = '') {
  return url.replace(/^https?:\/\/[^/]+/, '').split('?')[0].replace(/^\/api\/v1\//, '').replace(/\/$/, '');
}
export function successNotification(config, body) {
  if (config.notifications === false) return null;
  if (config.notifications?.success) return { type: 'success', message: config.notifications.success };
  const method = config.method?.toLowerCase();
  if (!['post', 'put', 'patch', 'delete'].includes(method)) return null;
  const path = apiPath(config.url), data = body?.data || {};
  // Authentication changes are announced after AuthProvider commits the change.
  if (path.startsWith('auth/') || path === 'tenants' || /^tenants\/\d+\/activate$/.test(path) || path === 'tenant/switch') return null;
  if (path === 'pos/quotes') return { type: 'info', message: 'Quote calculated. Review the totals before completing the sale.' };
  if (path === 'billing/checkout' || path === 'billing/portal') return { type: 'info', message: path.endsWith('portal') ? 'Opening subscription management in Stripe.' : 'Opening Stripe Checkout. Subscription access awaits verified payment confirmation.' };
  if (/^billing\/checkout\/\d+\/expire$/.test(path)) return { type: 'success', message: 'Pending Checkout expired.' };
  if (path === 'workspace/imports') return { type: data.status === 'invalid' ? 'warning' : 'info', message: data.status === 'invalid' ? 'The import has validation errors. Review the rows before importing.' : 'Import validated. Review the preview before importing products.' };
  if (/^workspace\/imports\/\d+\/post$/.test(path)) return { type: 'success', message: 'Products imported successfully.' };
  if (path === 'workspace/exports') return { type: data.status === 'ready' ? 'success' : 'info', message: data.status === 'ready' ? 'Export is ready to download.' : 'Export requested.' };
  if (path === 'inventory/reconcile') return { type: data.reconciled ? 'success' : 'warning', message: data.reconciled ? 'Stock ledger reconciled successfully.' : 'Stock reconciliation found issues. Review the results.' };
  if (path === 'legacy/financial-reconciliation') return { type: data.issues?.length ? 'warning' : 'success', message: data.issues?.length ? 'Reconciliation evidence captured. Some records need review.' : 'Reconciliation evidence captured.' };
  if (path === 'legacy/catalog-map') return { type: data.unresolved > 0 ? 'warning' : 'success', message: data.unresolved > 0 ? 'Catalog mapping completed with exceptions to review.' : 'Catalog mapping completed.' };
  if (method === 'delete') {
    if (/^products\/\d+\/variants\/\d+$/.test(path)) return { type: 'success', message: 'Product variant archived successfully.' };
    if (/^team\/\d+$/.test(path)) return { type: 'success', message: 'Team access removed for this business.' };
    const resource = path.replace(/\/\d+$/, '');
    if (resources[resource]) {
      const archived = resource === 'products' || /^catalog\/(categories|brands|units|attributes|tax-classes|price-lists)$/.test(resource);
      const deactivated = resource.startsWith('operations/') || /^catalog\/(branches|locations)$/.test(resource);
      return { type: 'success', message: `${resources[resource]} ${archived ? 'archived' : deactivated ? 'deactivated' : 'deleted'} successfully.` };
    }
  }

  const actions = [
    [/^pos\/held-carts$/, 'Cart held successfully. Recall it when you are ready.'],
    [/^pos\/held-carts\/\d+\/discard$/, 'Held cart discarded.'],
    [/^pos\/checkout$/, 'Sale completed successfully.'],
    [/^sales\/\d+\/payments$/, 'Payment recorded successfully.'],
    [/^sales\/\d+\/returns$/, 'Return request saved for approval.'],
    [/^sales\/\d+\/void$/, 'Sale cancellation recorded.'],
    [/^returns\/\d+\/approve$/, 'Return approved.'],
    [/^returns\/\d+\/post$/, 'Return and credit posted.'],
    [/^returns\/\d+\/refunds$/, 'Confirmed refund recorded.'],
    [/^returns\/\d+\/exchange$/, 'Exchange completed successfully.'],
    [/^cash\/sessions$/, 'Register shift opened.'],
    [/^cash\/sessions\/\d+\/close$/, 'Register shift closed.'],
    [/^cash\/sessions\/\d+\/movements$/, 'Cash movement recorded.'],
    [/^inventory\/(adjustments|counts)$/, 'Stock document saved for approval.'],
    [/^inventory\/(adjustments|counts)\/\d+\/post$/, 'Stock document approved and posted.'],
    [/^inventory\/reservations$/, 'Stock reserved successfully.'],
    [/^inventory\/reservations\/\d+\/release$/, 'Stock reservation released.'],
    [/^purchasing\/orders$/, 'Purchase order saved as a draft.'],
    [/^purchasing\/orders\/\d+\/approve$/, 'Purchase order approved.'],
    [/^purchasing\/orders\/\d+\/receipts$/, 'Goods receipt posted.'],
    [/^purchasing\/receipts\/\d+\/returns$/, 'Supplier return posted.'],
    [/^purchasing\/payables\/\d+\/settlements$/, 'Supplier payment recorded.'],
    [/^expenses\/categories$/, 'Expense category added.'],
    [/^expenses$/, 'Expense saved for approval.'],
    [/^expenses\/\d+\/approve$/, 'Expense approved and posted.'],
    [/^workspace\/settings$/, 'Business settings saved.'],
    [/^workspace\/templates\//, 'Notification template saved.'],
    [/^workspace\/support$/, 'Support case opened.'],
    [/^workspace\/alerts\/\d+\/read$/, 'Notification marked as read.'],
    [/^legacy\/sku-policy$/, 'SKU mapping policy approved.'],
    [/^legacy\/map-customers$/, 'Legacy customer reconciliation completed.'],
    [/^legacy\/customer-links\/\d+$/, 'Customer link saved.'],
    [/^pos\/branches\/\d+\/activate$/, 'Branch activated for the new POS.'],
    [/^products\/\d+\/images$/, 'Product image uploaded.'],
    [/^products\/\d+\/variants(?:\/\d+)?$/, 'Product variant saved.'],
  ];
  const action = actions.find(([pattern]) => pattern.test(path));
  if (action) return { type: 'success', message: action[1] };
  const resource = path.replace(/\/\d+$/, '');
  if (resources[resource]) return { type: 'success', message: `${resources[resource]} ${method === 'delete' ? (resource === 'products' ? 'archived' : 'removed') : /\/\d+$/.test(path) ? 'updated' : 'created'} successfully.` };
  return { type: 'success', message: 'Changes saved successfully.' };
}
