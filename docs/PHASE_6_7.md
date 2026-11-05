# Returns, financial reconciliation and SaaS operations

Implemented and applied locally on 2026-10-20 for the existing Laravel 11.8 / React JavaScript workspace. See [STRIPE_BILLING.md](STRIPE_BILLING.md) for Stripe test setup, architectural decisions and production gates. Historical Phase 0–5 descriptions remain in their original guides.

## Use the new screens

Sign out/in to refresh the role permissions after initialization. Start the backend and frontend as described in [IMPLEMENTATION.md](IMPLEMENTATION.md). PowerShell paths containing spaces need quotes; to use frontend port 3002:

```powershell
Set-Location 'POS Frontend'
npm.cmd run dev -- --port 3002
```

| Screen | Workflow |
| --- | --- |
| Sales & receipts → Return/cancel | Original invoice lines, quantities and condition; return request/approval/posting; separate confirmed refunds |
| Returns | Posted credit, damaged quarantine, refunds and linked replacement sale |
| Register shifts | Open/history, collections/refunds by method, cash in/out, close with counted cash and stored variance |
| Expenses | Categories, draft expense and authorized approval; cash expenses affect the shift |
| Reports | Tenant-local date/branch filters, sales/discount/tax/return/collection/refund/expense totals, current balances and reconciliation |
| Billing / Pricing | Monthly/yearly proposed plans, usage, subscription status, Checkout and Customer Portal, invoice history |
| Workspace tools | Receipt settings, return window, audit, in-app alerts/templates, private CSV import/export, support and operations status |

The existing branch activation requirement remains. The implementation does not automatically enable the new POS on a legacy branch or import historical sales as new transactions.

## Financial rules

Returns reference the original sold lines. Authorized staff can request/approve returns with no fixed day limit by default; set `return_days` later in workspace settings. Quantities cannot exceed remaining returnable units. Posting uses cumulative allocations of **stored original** discounts, tax and line totals, so split returns sum exactly to the original amounts even when current catalog prices change. Requested, approved and posted timestamps remain distinct.

Resellable returns add stock through the single inventory posting service. Damaged goods produce quarantine records and do not increase sellable balances. Cancellation posts one full approved return; a sale with a posted return cannot also be canceled. Same-key retries return the prior result; different concurrent requests cannot return/refund/restock the same entitlement twice.

Credit reduces the unpaid invoice balance first. Refunds cannot exceed either unrefunded return credit or money collected above the remaining invoice value. Cash refunds require adequate cash and an open shift in the original branch. Card/bank refunds require the confirmed external reference; this records the completed refund and does not send money through a gateway. Negative, excess or precision-invalid amounts fail.

A replacement sale links atomically to its return in the same branch and for the same customer. Select that original customer and a shift in the original branch when choosing replacement items. Only available collected credit transfers; an internal exchange method cannot be submitted as a normal payment. A failed replacement rolls back credit, payments and inventory together. Any credit exceeding the replacement remains available for an authorized refund.

Cash closing stores expected amount, actual count, variance and a method snapshot while holding the same tenant lock as receipts/refunds/expenses. Postings cannot enter a closed shift. An authorized new shift may then open on the register. Expenses are drafts until approved; cash expenses reduce the selected drawer, while confirmed external expenses do not.

Reports separate invoice value, tax, return credit and cash flow. Sales use sold time; returns use posted time; receipts/refunds/expenses use recorded time, including collections on older invoices. Current receivables/refund obligations are lifetime balances and are labeled accordingly. Margin is an estimate from historical unit-cost snapshots; damaged returns retain their cost. It is not verified COGS, net profit or a general ledger.

Legacy reconciliation snapshots `ledger` and `ledgers` independently along with daily accounts, original orders/returns and dealers. It records source IDs, JSON/checksums and ambiguous balances without changing source rows or merging namespaces. Historical refund method/time/payment status remain **unknown**. Populated history requires evidence and operator accounting review; this phase does not invent journal entries or refund records.

## Data, permissions and operations

All new tenant business records have tenant-scoped queries, indexes and relational constraints. Original schemas/rows remain intact; migrations are additive and forward-only. The installer adds advanced permissions to system roles once and preserves custom roles/grants. Billing uses owner/`billing.manage`; return approval, refunds, cash movements/close, expenses, reports, audit and imports/exports use separate permissions. UI checks supplement server authorization.

CSV import previews up to 1,000 new simple/service/digital products (2 MB UTF-8 CSV), verifies existing active units/unique SKUs, then posts all-or-nothing with quota rechecks. It does not import opening stock or overwrite existing products. Columns: `sku,name,unit_code,default_cost,default_price,product_type`. CSV exports cover catalog, sales, customers and stock movements, up to 50,000 records interactively. Private authorized downloads expire after 24 hours; spreadsheet formula prefixes are escaped. Uploaded private catalog CSV files are retained as import evidence.

Receipt settings are captured on new sale snapshots. In-app low-stock alerts use literal allowed placeholders; templates do not execute Blade/PHP. Request logs include a generated `X-Request-ID`, tenant, route template, status and duration, excluding request bodies/secrets. Readiness checks DB/cache availability; it does not prove queue freshness or backup health. Support cases are tenant-scoped; resolution is a trusted audited CLI action, without user impersonation:

```powershell
php artisan support:resolve CASE_REFERENCE --resolution='Reviewed and resolved'
php artisan operations:maintenance
php artisan operations:backup --restore-drill
```

`operations:maintenance` refreshes observed usage, cleans expired private exports and retries stalled verified events. Run the Laravel scheduler continuously. Optional daily backups default off until configured. The local backup driver uses Windows DPAPI encryption tied to this Windows user; encrypted DB/backend files and private evidence are stored under `docs/audit/private`, outside public routes. The restore drill creates a separate `bu_restore_*` database and verifies rows, fingerprints and backend files, never restoring over the source. Include the frontend release artifact/source in deployment backups separately. Production needs independent off-host retention and key recovery; this local same-host drill does not provide that.

## Installation and evidence

The pre-change encrypted backup is `backup_828619fc1a0a4fde8a5404896a58a954`, restored and verified into isolated `bu_restore_78d59dfea2d5efbd`. Both new migrations and the installer were rehearsed there; original column values/counts and `.env` were unchanged. Separate MariaDB processes verified competing returns, identical/different refund requests, cancellation versus return, close versus refund, and resource quotas. Financial and inventory totals reconciled.

For another installation, finish Phase 0–5 and create/verify a backup first. Apply only these additive migrations and initialize once:

```powershell
# From POS backend
composer install
php artisan migrate --path=database/migrations/2026_10_20_140000_create_returns_cash_reports.php
php artisan migrate --path=database/migrations/2026_10_20_150000_create_saas_settings_operations.php
php artisan operations:initialize-advanced --apply
php artisan test --compact --do-not-cache-result

# From POS Frontend
npm.cmd ci
npm.cmd test
npm.cmd run build
```

Do not run `migrate:fresh`, replay legacy seeders, regenerate `APP_KEY`, or drop the additive tables to roll back after trading. Resolve issues through forward corrections; restore a reviewed backup only with a controlled cutover that preserves subsequent transactions.

Private evidence files: `phase-6-7-rehearsal.json`, `phase-6-7-concurrency.json`, `phase-6-7-apply-local.json`, pre/post audit JSON, encrypted backup verification, Composer/npm audits. These contain operational detail and must stay out of source control/public hosting.

Local application verified original column values/counts and `.env` unchanged. The database now has 107 tables and 51 applied migrations. No branch was activated, no Stripe subscription was created, and no automatic charge occurred. The post-install encrypted backup `backup_2903e437e56e4907a01226efe3d5ebc3` restored into isolated `bu_restore_44134a6efbeaefd1`; schema/counts/fingerprints and 2,948 backend files reconciled. Final verification: **67 backend tests / 1,011 assertions**, **25 React tests**, and the Vite production build passed. See [verification record](audit/phase-6-7-verification.md).

## Security review and remaining production gates

The 2026-10-20 lockfile security review found **59 advisory entries across 16 backend packages** and **5 frontend vulnerability entries (2 critical, 3 moderate)**. Stripe SDK is not among the affected packages. Frontend `npm audit fix --dry-run` proposed no compatible changes; available remediations require Vitest 5 and React Router 7 major upgrades. The affected frontend entries include Vitest/mocker/tinypool tooling and React Router packages. Do not expose the development/test server publicly. Backend findings include the inherited framework, HTTP, Markdown/PDF and other dependencies. They require a dedicated compatible upgrade/remediation and regression review before Internet deployment; these phases do not assert that the inherited stack is production secure.

Also pending: owner UAT and actual populated historical accounting review, Stripe test-account Checkout/Portal/recurring lifecycle verification with real test keys/Price IDs, staging HTTPS, queue supervision, off-host backups/retention and operational alert routing. No staging server exists yet. Final approved plan terms and explicit live-charging approval are required separately. Phase 8 launch/cutover remains unstarted.
