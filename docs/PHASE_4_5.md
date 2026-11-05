# Inventory, purchasing and the new POS

> Phase 6 and 7 now extend this implementation with returns/refunds/exchanges, cash close/movements/expenses, financial reports and Stripe SaaS subscriptions. See [PHASE_6_7.md](PHASE_6_7.md). The outstanding Phase 6 descriptions below reflect the earlier milestone.

Implemented and applied locally on 2026-10-19. The additive migration and initialization were rehearsed on an encrypted backup restore before local application. Original legacy rows, amounts, stock quantities, credentials and `.env` remain unchanged. No branch was activated automatically.

## Start using the workspace

Run Laravel and React using [IMPLEMENTATION.md](IMPLEMENTATION.md), then sign in again so the new permissions load. PHP BCMath is now an explicit Composer requirement. Use these screens:

| Screen | Purpose |
| --- | --- |
| Products / Catalog setup | Units, SKUs, barcodes, optional tax classes and rates, price lists |
| Branches & locations | Confirm actual physical stock locations; MAIN is a setup placeholder |
| POS activation | Owner reviews the cutover snapshot and enables a branch |
| Inventory | Balances, movements, adjustment approval, stock counts, reservations, ledger reconciliation |
| Purchasing | Draft/approve purchase orders, partial deliveries, rejected goods, supplier returns, payables and recorded settlements |
| Customers | Retail customer identities, separate from staff logins |
| Register shifts | Open a register with counted opening cash; view payments by method and expected cash |
| Point of sale | Scan SKU/barcode and press Enter, search, cart, customer, server quote, split payments, checkout |
| Sales & receipts | Immutable invoice snapshots, printable receipt and later collections on balances due |
| Legacy mapping | Catalog evidence and reviewed customer links for original order user IDs |

For the legacy business, finish catalog reconciliation first. An owner opens **POS activation**, selects a branch, reviews opening quantities and physical locations, confirms pricing/credit and activates it. All legacy stock rows are imported once at this transaction's cutover instant. Sales history is never replayed into opening stock. The local source currently has no stock or order rows; populated test fixtures verify the conversion.

Because the old schema has no branch ownership, the first activation freezes **all old Blade stock/order/return/catalog mutation routes for this business**, including legacy GET cancellation/deletion routes. The legacy writer middleware and new postings lock the same tenant row, so an in-flight old sale finishes before the snapshot or is rejected after activation. Original invoices remain readable. Other new branches have their own activation flags. Activation is an owner action and has no reversal button; restoring a verified backup is the recovery path before trading resumes.

The preview hash must match the source at activation. Quantity changes since catalog mapping are captured at cutover; identity or price changes require mapping review. Unmapped, negative, inactive-location or already-stocked rows block import. The opening batch retains source IDs, quantities, checksums, cutover time and the reviewed hash. Changing a used unit, tracking mode, inventory flag, register branch/location or location ownership is restricted to preserve history.

## Inventory and purchasing rules

`InventoryPostingService` is the sole application writer of `inventory_balances` and `stock_movements`. Short database transactions lock the tenant first, then balances in deterministic document order. This deliberately serializes postings within a tenant in this release; throughput optimization must preserve the lock order and idempotency contract. No network or external payment calls occur inside these transactions.

Every physical change records signed quantity, tenant, location, variant, actor, reference, unique source event and optional cost snapshot. MariaDB checks prevent negative/reserved-over-stock balances, and triggers reject movement updates/deletes. Corrections are new postings. Request keys and payload hashes prevent duplicate posting and reject reusing a key for different data. Failed checkout/receiving/return transactions roll back their documents, movements, payments and invoice sequence together.

Adjustments and counts are drafts until an authorized approver posts them. An inventory clerk can draft but cannot approve. Owners/managers may hold both permissions; enforced two-person approval is not a configured requirement. Counts capture balance versions and reject posting when stock or reservations have changed. Reservations hold available stock, can be released repeatedly without double release, and expire before availability-sensitive stock/quote operations. Displayed reservation status is updated lazily by those operations or ledger reconciliation. Checkout does not silently consume a separate reservation; release it before selling its units.

Units permit zero to four decimal places. Fractional quantities must match the unit precision; services/digital products do not change physical stock. Batch/serial modes remain catalog metadata for the optional tracking module; transactions using those modes are explicitly rejected. Transfers, lots/expiry, serial allocations and inventory valuation layers are outside the implemented phase tasks. Cost snapshots are planning/source values, not a verified COGS or general ledger.

Purchase orders require approval. Accepted units add stock and a receipt-based supplier obligation; rejected units do neither and remain outstanding. Receipts cannot exceed the remaining order quantity. Supplier returns reference original accepted receipt lines and cannot exceed cumulative returnable quantities or available stock. Returns reduce the original payable; a return after settlement can leave an explicitly labeled supplier credit. Partial return credits use cumulative decimal allocation so splitting a return cannot create extra cents. Settlements record confirmed external payments, without moving bank funds. Ledger and payable reconciliation APIs expose discrepancies without rewriting history.

## Sales, prices and payments

Owner decisions recorded for this implementation: named customers may use branch-controlled credit, walk-ins must pay in full, catalog prices exclude tax, and amounts round half-up to currency minor units. Fixed order discounts apply before tax and require `sales.discount`. The server allocates discounts across lines deterministically, rounds each extended line and tax amount, and sums those stored amounts. Prices/quantities arrive as decimal strings; PHP floats are never used for arithmetic. Optional active price-list tiers select the highest eligible minimum quantity in the tenant currency.

Taxed products require an active class and exactly one effective rate for the branch jurisdiction on the tenant's local sale date. A product without a tax class has zero configured tax; no tax rate or registration is invented. Configure the actual jurisdiction/rates before selling taxed items. Supported currency precision is centralized in `Operation::scale()`; PKR uses two places, selected zero/three-place currencies are explicit, and unrecognized currencies block activation rather than guessing.

Quotes expire after ten minutes. Checkout recalculates prices, discounts, tax and product snapshots and rejects changed/expired quotes. Only server-selected prices and totals are persisted. It checks current availability under the posting lock; a quote does not reserve stock. One quote can produce one sale. Invoice sequences are tenant/branch/year scoped and transactionally locked. Product, SKU, unit, price/cost, tax/discount, customer and business/branch snapshots preserve receipts when catalog data changes.

Cash, external terminal card and confirmed bank-transfer methods are recorded payments; no payment gateway is integrated. Non-cash entries require the confirmed external reference. Enter the **amount applied after giving change**; applied payments cannot exceed the due amount. Named-customer unpaid/partial balances require branch credit permission. Subsequent collections use an open shift in the original branch and are idempotent. Completed sales cannot be deleted through the new API. Payment status is independent of completed-sale status.

React retains the checkout request key and payment payload after an unconfirmed network result, disables edits and offers **Retry same checkout**. The backend returns the saved sale on retry. Double clicks are also blocked immediately in the UI. Tenant switching remounts the workspace and clears its cart. A cashier's open register shift and payment reconciliation are implemented; closing, cash movements, retail returns/refunds/exchanges and financial reports remain phase 6.

Legacy school margins, order discounts and received/remaining fields stay in their original tables and catalog mapping evidence. New catalog pricing does not reinterpret those historical figures. Legacy retail users referenced by orders are mapped to `customers` only when they are clearly non-staff. Staff, missing or ambiguous users enter the review queue. Original users/passwords and `orders.user_id` are never changed, and historical payments/refunds are never fabricated.

## Installation, checks and recovery

For another installation, back up and restore first. Apply only the additive migration after phases 2–3; never use `migrate:fresh` or rerun legacy seeders:

```powershell
# POS backend
composer install
php artisan migrate --path=database/migrations/2026_10_19_130000_create_inventory_purchasing_pos.php
php artisan operations:initialize --apply
php artisan test --do-not-cache-result

# POS Frontend
npm.cmd test
npm.cmd run build
```

Initialization grants only new operations permissions to existing system roles once per tenant, preserves previous/custom grants, creates payment methods and maps safe customers. It never activates a branch. New businesses receive operations permissions through the normal role seed and payment methods during activation. Custom roles must include the documented read dependencies; permissions and every parent/child reference are tenant scoped, with composite foreign keys as a second guard.

Local-only helpers in `scripts/` enforce a verified `bu_restore_*` connection with its own connection name and Schema builder. `apply-phase-4-5.php rehearsal <backup-id>` fingerprints all original legacy rows before/after migration. `verify-phase-4-5-concurrency.php` creates fixtures only in that restore and launches separate PHP processes against a common barrier. `apply-phase-4-5.php apply-local` requires successful matching rehearsal/concurrency evidence. Private backup/evidence files are ignored by Git. See [verification](audit/phase-4-5-verification.md), [API contract](api-contracts/v1-inventory-pos.md), [pricing decision](decisions/ADR-003-inventory-pricing-cutover.md) and [recovery](RECOVERY.md).

Actual browser/scanner/printer UAT and HTTPS staging remain pending because no staging server is available. Existing dependency advisories must be resolved before public deployment. No production branch cutover is claimed.
