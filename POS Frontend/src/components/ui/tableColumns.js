// Explicit display metadata, not numeric-string guessing (phone/SKU/IDs stay left).
const numeric = new Set([
  'on_hand', 'reserved', 'quantity', 'quantity_delta', 'qty', 'net_units',
  'expected_qty', 'counted_qty', 'variance_qty', 'ordered_qty', 'received_qty', 'returned_qty', 'accepted_qty',
  'unit_cost', 'unit_price', 'default_cost', 'default_price', 'line_total', 'grand_total', 'subtotal',
  'total', 'amount', 'original_amount', 'outstanding_amount', 'balance_due', 'paid_total',
  'discount_total', 'tax_total', 'net_sales_excluding_tax', 'invoice_count', 'open_sessions',
  'opening_cash', 'variance', 'collections', 'refunds', 'expected_cash',
  'rate_percent', 'min_qty', 'quantity_precision', 'sort_order',
]);
export const columnClass = key => numeric.has(key) ? 'numeric' : undefined;
