# V1 inventory, purchasing and POS

All paths below start `/api/v1`. They use the existing Sanctum session, CSRF protection, active-tenant session selection and tenant permission gates. Supplying `tenant_id` never changes scope. Decimal inputs are **strings**, with at most 12 integer digits/four decimal places and unit/currency precision enforced. Lists paginate; callers must follow `last_page`. Details/actions wrap results in `data`.

Mutating document creation/checkout/settlement/open-shift requests require `idempotency_key` (8–100 ASCII letters/digits/colon/underscore/hyphen). Retries must preserve it and the request data. Successful responses are retained; conflicting reuse returns 409. Validation is 422, missing/foreign resources 404 or scoped validation 422, denied permission 403, unauthenticated 401, expired session/CSRF 419. Domain 409 messages are safe actionable strings. A failed transaction stores no idempotency success record.

| Method / path | Permission / behavior |
| --- | --- |
| GET `operations/{customers,suppliers,registers,payment-methods}` and `/{id}` | Tenant-scoped lookup/search/detail; customer view, purchasing view, cash open or payment record respectively |
| POST/PATCH corresponding reference paths | Customer manage, purchase manage or settings manage; archive with `active:false`, no history deletion |
| GET `pos/products?search=...` | `products.view`; product/name/variant/SKU search, exact barcode; `scan=1` restricts to exact SKU/barcode |
| GET `inventory/{balances,movements,adjustments,counts,reservations}` | `inventory.view`; optional `location_id`, `variant_id` filters |
| GET `inventory/{adjustments,counts}/{id}` | Document and items |
| POST `inventory/adjustments` | `inventory.adjust`; location, reason, items `{variant_id,quantity_delta}` |
| POST `inventory/counts` | `inventory.adjust`; location, reason, items `{variant_id,counted_qty}` |
| POST `inventory/{adjustments,counts}/{id}/post` | `inventory.approve`; repeatable post, stale count conflict |
| POST `inventory/reservations` | `inventory.reserve`; location, variant, quantity, reference, minutes (1–1440, default 15) |
| POST `inventory/reservations/{id}/release` | `inventory.reserve`; repeatable release |
| POST `inventory/reconcile` | `inventory.approve`; expires reservations then compares ledger/held sums |
| GET `purchasing/{orders,receipts,returns,payables,settlements}` and `/{id}` | `purchases.view` |
| POST `purchasing/orders` | `purchases.manage`; supplier, location, items `{variant_id,ordered_qty,unit_cost,tax_rate?}` |
| POST `purchasing/orders/{id}/approve` | `purchases.approve`; repeatable approval |
| POST `purchasing/orders/{id}/receipts` | `purchases.manage`; items `{purchase_order_item_id,accepted_qty,rejected_qty?}` |
| POST `purchasing/receipts/{id}/returns` | `purchases.approve`; reason, items `{goods_receipt_item_id,quantity}` |
| POST `purchasing/payables/{id}/settlements` | `purchases.settle`; amount, confirmed external reference |
| POST `purchasing/reconcile` | `purchases.view`; compare receipt, return, settlement and payable amounts |
| GET `pos/branches/{id}/preview` | `settings.manage`; source rows, issues, reviewed hash and global legacy freeze scope |
| POST `pos/branches/{id}/activate` | Owner plus `settings.manage`; preview_hash, confirm_locations:true, allow_credit, optional tax_jurisdiction; import once/freeze/activate |
| GET/POST `cash/sessions` | `cash.open`; list accessible open shifts; create with register_id and opening_cash |
| GET `cash/sessions/{id}/reconciliation` | `cash.open`; own shift or `cash.manage`; recorded method totals/expected cash |
| POST `pos/quotes` | `sales.create`; cash_session_id, optional customer_id/price_list_id/discount_amount, items `{variant_id,qty}` |
| POST `pos/checkout` | `sales.create` + `payments.record`; quote_id, payments array, idempotency key |
| GET `sales` and `sales/{id}` | `sales.view`; optional customer_id and due=1; receipt items/payments/snapshots/balance_due |
| POST `sales/{id}/payments` | `payments.record`; cash_session_id, nonempty payments array and idempotency key |
| GET `legacy/customer-links` | `legacy.view`; original order/user mapping status, safe snapshots |
| POST `legacy/map-customers` | Legacy owner plus `legacy.manage`; rerunnable safe split, unresolved staff/missing identities |
| PATCH `legacy/customer-links/{id}` | Owner plus `legacy.manage`; customer_id chosen after review; original order untouched |

Payment entries are `{method_id, amount:"12.35", reference?:"confirmed-terminal-reference"}`. Methods must be distinct within a request. Empty checkout payments are allowed for zero-price sales or eligible named-customer credit. There is no card capture API. All payments are recorded confirmations, not provider gateway outcomes.

Example checkout:

```json
{"idempotency_key":"checkout-8c8fe7a4","quote_id":42,"payments":[{"method_id":1,"amount":"50.00"},{"method_id":2,"amount":"20.00","reference":"terminal-123"}]}
```

`POST pos/quotes` returns `id`, `expires_at` and a `quote` containing server subtotal, fixed discount, tax, grand total, currency/minor units, location/register/branch and immutable line candidates. Checkout returns the sale ID and invoice number. A consumed quote submitted with a different key conflicts, while the original key returns the original response.

No update/delete endpoint exists for posted stock movements, sale lines, completed sale amounts, receipt lines or recorded payments. Correction/reversal workflows must be explicit new documents; retail returns/refunds are phase 6.
