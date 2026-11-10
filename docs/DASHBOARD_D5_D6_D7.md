# Dashboard D5–D7 — Specialist data, platform workspace and rollout

Implemented for the user's combined D5–D7 request, 2026-11-05 (Asia/Karachi), against the [dashboard specification](UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md). That request authorizes these phases together; earlier documents describe the authorization at the time of their individual phases.

D5/D6 engineering and D7 local verification/rollout preparation are implemented. **A live pilot, cashier/manager feedback, real-device acceptance and human sign-off are still pending. No staging server exists yet.** No deployment, automatic platform grant, role-template change, paid subscription, migration or working-database write is part of this change. New dashboards are disabled by default until a pilot is selected.

## Backend support confirmed before implementation

This is a source/schema audit, verified with actual posting services in an isolated SQLite test database. It is not a claim to have audited live MySQL data or measured production performance. Existing D2 scope, DECIMAL aggregation, D4 cashier/manager views and UX-4 specialist summaries provide the foundation.

| Requested information | Existing source and meaning | Implemented presentation |
| --- | --- | --- |
| Stock KPIs/low stock | Scoped `inventory_balances`, variants/base units; existing summary/alerts | Current counts, separate unit quantities and real stock links |
| Movement trend | Posted `stock_movements.quantity_delta`, `occurred_at`, variant base unit | Received/issued by business-local day; positive issued quantities; never add EA to KG |
| Adjustments | `stock_adjustments.status` | Current status counts, existing approval links, **Stock adjustment** opens the existing modal |
| Transfers | No transfer workflow/source | Explicitly unavailable; no new transfer transaction invented |
| PO status/open orders | Scoped `purchase_orders` | Current status counts, approved/partial order list, source documents and existing create modal |
| Expected/overdue receipts | No stored delivery deadline | Clearly unavailable; open orders are not labelled scheduled or overdue deliveries |
| Supplier activity | Suppliers on permitted POs; scoped `supplier_payables` through receipts | Current order/open counts and outstanding payables, not the full supplier directory or risk scoring |
| Revenue/expenses | Existing recognition formulas; posted `retail_expenses` by `posted_at` | Aligned daily series, net sales excluding tax and posted expenses; their difference is not net profit |
| Receivables/refunds due | Existing summary balance projections | Current lifetime balances; independent of selected activity dates |
| Cash/bank | `sale_payments`/`refunds` with method snapshots | Recorded collections/refunds by method type; bank payments are flows, not balances |
| Reconciliation alerts | Closed `cash_sessions` with non-zero variance | Period count; own shift rows need `cash.open`, other staff rows additionally need `cash.manage` |
| Bank balances/verified profit | No authoritative bank ledger or verified COGS | Explicitly unavailable; no estimated profit displayed as verified profit |
| Platform tenant/subscription totals | `tenants`, current `tenant_subscriptions` | Counts, UTC tenant-creation trend, recorded subscription statuses |
| Platform incidents | Billing webhook statuses, open/uncollectible `saas_invoices`, optional `failed_jobs` | Counts only; absent stores are unavailable, not zero |
| MRR | Provisional plan catalog prices are not authoritative recurring revenue | Unavailable, with the missing basis explained |
| Associate sales | `sales.cashier_membership_id` and posted credits against those invoices | Mandatory personal sales, own trend/receipts, distinct named customers on own invoices if `customers.view` |
| Assigned customers/follow-ups | No assignment/follow-up records or separate assisted-sale attribution | Unavailable; do not claim cashier attribution measures assisted selling |

No new table is required for these read-only projections. Existing migrations and billing/stock/sales data remain the source of truth.

## Screens and data contracts

The existing Overview (`/`) chooses a layout from effective server capabilities. Role names select presentation only. Owners/admins, managers and cashiers keep their existing D3/D4 compositions. Custom memberships with inventory, purchasing or reporting grants receive the corresponding specialist panels. A membership with the explicit `sales_associate` role code can use the personal workspace; sales-only custom roles also use it when the server advertises `personal-sales`. A reporting grant on an associate does not make this personal endpoint return company sales.

The platform route is **`/platform/dashboard`**, with its own shell, navigation and request flow. A platform-only user can land there after login when the pilot is enabled. Tenant users see a Platform dashboard navigation item only when the session advertises an explicit platform permission and the platform flag. No tenant switcher or tenant API request is needed in the platform shell. A user with a separate active tenant membership can explicitly return to the business workspace.

New tenant GET resources (under `/api/v1/dashboard/`):

| Resource | Required effective permission, in addition to `dashboard.view` and pilot eligibility | Pagination |
| --- | --- | --- |
| `inventory-trend` | `inventory.view` | Base units with activity; each unit has the complete selected daily series |
| `purchase-orders` | `purchases.view` | Approved/partial orders |
| `supplier-overview` | `purchases.view` | Suppliers on permitted orders |
| `accounting-trend` | `reports.view`, no explicit personal-only restriction | Daily series; finite method-type groups |
| `accounting-exceptions` | `reports.view`, no personal-only restriction | `cash.open` permits own shift rows; `cash.manage` also permits other staff rows; report count can include additional permitted shifts |
| `personal-sales` | `sales.view` | Own recent receipts; summary/trend always own |

Lists are server paginated, **five per initial dashboard request**, independently per panel. Branch/date changes reset pages. Only the requested unit page is aggregated for movement details. Current PO/supplier/adjustment snapshots are labelled separately from reporting-period activity. The existing endpoint limits still cap list pages and reporting ranges (at most 366 calendar days). No list page is used to calculate a total.

New platform resource: `GET /api/v1/platform/dashboard`, authenticated separately through `RequirePlatformPermission`. `platform/context` advertises pilot availability. The allowlisted payload contains aggregate counts/trends only: no tenant names/emails, invoice documents, customer/payment identifiers, provider payloads, job payloads or raw errors. UTC is explicit for platform creation dates; status/incident counts are current snapshots. Subscription counts include legacy/grandfathered records and do not imply all are paid accounts.

Both shells reuse local Inter, semantic light/dark tokens, Remix icons, existing loaders/errors, accessible SVG charts and exact-value disclosure tables. No UI library or payment implementation is replaced. Unsupported values remain **Unavailable**. Decimal strings are retained through financial aggregation and exact display; chart positioning alone uses JavaScript numbers.

## Security and reconciliation

- Tenant and membership come from authenticated server context, never a React tenant ID. Existing `RecordScope` constrains locations, branches, parent documents, payments/refunds and explicit personal restrictions.
- `personal-sales` forces actor scope even when `reports.view` is also granted. Receipt links retain `own=1`; operational APIs still independently authorize them.
- Explicit personal-sales restrictions override reporting grants for accounting projections. Reports permission does not grant shift-document permission.
- Pilot and permission checks run before dashboard cache reads. Keys retain actor, tenant, branch, date, access fingerprint, data revision, resource and page. Permission revocation and pilot rollback cannot serve a previously authorized cached result.
- The React loader verifies response metadata and reloads current capabilities. It clears previous scopes while loading, rejects mismatched responses and discards late responses. Secondary source failures stay within their panel; authorization failures clear the complete dashboard.
- Revenue uses the existing original-invoice minus posted-credit formulas. Returns of older invoices are recognized on posting time, including tax/discount rules. A voided invoice is not subtracted twice.
- Movement reconciliation uses signed posted deltas. Expenses use posting dates rather than draft/entered expense dates. Method flows include amounts recorded against older invoices and keep exchange credit labelled separately from external cash.
- Accounting refuses mixed-currency payment/refund/expense flows and balances without a conversion policy, including collections recorded today against older invoices. Reporting dates do not silently make a foreign-currency collection safe to add.
- Existing tests compare signed daily/branch/total results with reports, including partial/damaged returns, cancellation, credit balances, DST boundaries and mixed-currency rejection. D5 tests add posted movement balance reconciliation, PO/supplier counts, expense midnight boundaries and mandatory associate scope.

## Controlled enablement and rollback

The repository's `.env.example` documents these controls. The current `.env` has not been edited.

```dotenv
DASHBOARD_API_ENABLED=true
DASHBOARD_CACHE_SECONDS=30
DASHBOARD_SPECIALIST_ENABLED=false
DASHBOARD_PILOT_TENANT_IDS=
DASHBOARD_PLATFORM_ENABLED=false
```

For an operator-selected pilot, set `DASHBOARD_SPECIALIST_ENABLED=true` and put the reviewed existing tenant IDs in `DASHBOARD_PILOT_TENANT_IDS` (comma separated). A non-empty allowlist enables only those IDs; malformed entries do not widen access. **An empty allowlist means all tenants once the specialist flag is enabled**, so retain an explicit list throughout the pilot. Eligibility does not grant any module permission. Normal Laravel config-cache deployment/restart procedures must apply the changed environment.

The platform flag is independent. Enable `DASHBOARD_PLATFORM_ENABLED` only for the intended deployment, and grant access to a reviewed existing user through the existing audited command:

```powershell
# Run from 'POS backend'; substitute an actual reviewed user ID.
php artisan platform:permission grant EXISTING_USER_ID --operator="Operator identity"
```

No command is run against the working database by this implementation. Platform grants never create tenant memberships. A numeric legacy Super Admin role or tenant owner is not automatically promoted.

Rollback specialists by setting `DASHBOARD_SPECIALIST_ENABLED=false`, applying configuration and refreshing the dashboard. The UX-4 summary remains available; new projections are refused before cache access. Disable the platform pilot separately with `DASHBOARD_PLATFORM_ENABLED=false`; its aggregates return 404 and its context advertises disabled state. Explicit access can also be revoked with `platform:permission revoke EXISTING_USER_ID --operator="Operator identity"`. Existing transactions/records remain intact. `DASHBOARD_API_ENABLED` is the broader existing API kill switch, not the specialist rollback mechanism.

Do not switch defaults merely because automated tests pass. On a separately restored UAT/staging environment, select the pilot roles/branches, run the [manual cases](UAT_TEST_CASES.csv), record feedback in [the pilot worksheet](DASHBOARD_PILOT_FEEDBACK.csv), and complete [the role matrix](DASHBOARD_ROLE_SIGNOFF.md). Expand the tenant allowlist in reviewed batches only after results are accepted. Empty the allowlist for general availability only after explicit release approval.

## Verification and release evidence

Final local verification, 2026-11-06: **293 React tests (25 files), 65 Laravel tests / 1,550 assertions, 316 browser checks and a passing production build**. Browser evidence comprises 126 specialist/platform checks, 144 existing-role checks and 46 POS checks. There are 102 current screenshots and 16 baseline screenshots; all 243 gallery links resolve. The 20 new manual UAT cases remain Not Run.

The [screenshot gallery](design/dashboard-d5-d7/index.html) compares the previous verified UX-5 build with D5/D6 and includes current role/POS regression captures. The [verification manifest](audit/dashboard-d5-d7-verification.json) records exact test counts, build assets, source hashes and evidence paths. Browser runs use synthetic HTTP fixtures; Laravel tests force an isolated in-memory database. These are separate layers, not a claim of a deployed end-to-end staging run.

Reproduction from the frontend directory:

```powershell
npm.cmd test -- --reporter=json --outputFile=../docs/audit/dashboard-d5-d7-react.json
npm.cmd run build
node scripts/dashboard-specialists-browser-smoke.mjs
node scripts/prepare-dashboard-regressions.mjs
node scripts/dashboard-d7-role-browser-smoke.mjs
node scripts/dashboard-d7-pos-browser-smoke.mjs
node scripts/record-dashboard-verification.mjs
```

Run Chrome suites sequentially; each starts its own temporary browser profile and synthetic server. The baseline screenshots have already been captured from the previous build; do not regenerate them from the new build. From the backend directory:

```powershell
php artisan test --filter="DashboardFoundationTest|StaffDashboardTest|SpecialistDashboardTest" --do-not-cache-result --log-junit=../docs/audit/dashboard-d5-d7-laravel.xml
```

Still required outside local engineering: staging/HTTPS deployment, representative MySQL volume/query plans, physical scanner/touch devices and supported-browser testing, screen-reader use by a human, cashier/manager pilot feedback, and product-owner role/release sign-off. These are recorded as pending, not fabricated passed results. Unsupported business workflows listed in the support table require their own approved backend scope.
