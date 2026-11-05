# Dashboard D2: permissions and API foundation

Implemented from the [dashboard specification](UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md) after [D1](DASHBOARD_D1.md). The user approved D2 and confirmed: **preserve existing staff access; apply explicit branch restrictions to selected memberships**. No existing staff account is automatically restricted, given another role, promoted to platform administration or charged.

## Scope and visible behavior

D2 supplies the Laravel foundation for role dashboards. At D2 delivery the React overview remained the D1 welcome page; the subsequently requested [D3 implementation](DASHBOARD_D3.md) now connects owner/admin widgets and source filters. No sample metrics were added to production screens. The product cost column follows the same cost permissions as the server instead of rendering costs for catalog-only readers.

The subsequently requested [D4](DASHBOARD_D4.md) adds cashier/manager profiles, personal cashier summaries, scoped staff activity and saved carts. The original D2 `shift-summary.held_sales` remains unavailable; D4's cashier endpoint supplies the actual private held count. Existing access-policy defaults and grants remain unchanged.

The implementation adds:

- Server-resolved tenant/platform context, allowed branches, effective role unions and dashboard capabilities.
- Explicit membership branch/personal-sales restrictions, enforced by a shared record scope across dashboard queries, operational lists/details, posting validation, reports and exports.
- Eleven dashboard collection/summary endpoints, plus a scoped recent-receipt detail endpoint.
- Decimal financial aggregates, tenant-local date buckets, return/cancellation definitions, scoped server caching, pagination and rate limits.
- Additive access tables, tenant-safe composite foreign keys and reporting indexes.
- Separate, audited platform permission provisioning. No platform grants are created by the migration.

## Access model

The existing primary `tenant_memberships.role_id` remains authoritative for ownership and existing administration. `tenant_member_roles` stores optional additional roles. Laravel returns the union of their explicit permissions; a role name or legacy numeric role ID does not grant dashboard access. Additional roles cannot bypass branch/personal restrictions, and the access API does not accept Owner as an additional role. Primary ownership and last-owner protections remain intact.

`tenant_member_access` stores `branch_scope` (`all` or `assigned`) and `sales_scope` (`all` or `own`). Missing policy rows preserve existing `all/all` behavior. With `assigned`, only records attributed to assigned branches are visible; an empty assignment yields no branch records. Locations without a branch are visible only in unrestricted, unfiltered tenant scope. Unknown policy values fail closed. `own` restricts sales and related sale/return/payment records to the membership's cashier ID and cash sessions to the member who opened them.

Restrictions are independent of permissions. Even a role union containing `reports.view` or `cash.manage` cannot bypass an explicit personal/branch restriction. Each request revalidates active membership before checking the cache. Posting rechecks the resolved access while holding the existing tenant transaction lock. Idempotent responses for restricted staff are bound to their actor and access fingerprint; a response generated under another actor/policy cannot be replayed.

The shared catalog, customers and suppliers are tenant directories, not branch-owned entities. Read permissions retain their established meaning. Restricted staff can transact against permitted branches, but tenant-wide administration, team/role management, billing administration, shared catalog/register/payment-method editing, legacy mapping and unscoped workspace notifications/audits are refused. These records have no reliable branch attribution. Scoped exports remain available. Legacy Blade administration also refuses explicitly restricted memberships; changing the active-business session cannot bypass that check. Logout remains available.

## Metric permission map

All dashboard requests require session authentication, active tenant membership, `dashboard.view`, and the existing subscription access policy.

| Data | Additional permission and scope |
| --- | --- |
| Sales summary / trend | `reports.view` or `sales.view`; without reports permission, only the actor's sales and related returns |
| Branch performance | `reports.view`, with no explicit personal-sales restriction; permitted branches only |
| Recent receipt list/detail | `sales.view`; personal scope without `reports.view`, and always subject to explicit restrictions |
| Top products | `products.view` plus sales/report access; same sales/return scope |
| Inventory quantities / stock exceptions | `inventory.view`; permitted stock locations; no valuation fields |
| Purchasing | `purchases.view`; permitted order/receipt locations and their payables |
| Accounting | `reports.view`, with no personal-sales restriction; permitted branches only |
| Open shifts | `cash.open`; own sessions unless `cash.manage`, with explicit restrictions still applied |
| Pending approvals | The corresponding inventory/purchase/return/expense approval permission |
| Platform context | Separate `dashboard.platform.view` platform grant, independent of all tenant roles |

Catalog default cost is omitted from read responses and CSV exports unless the actor has `products.manage`, `purchases.view` or `reports.view`, permissions which already provide catalog cost editing, procurement or financial reporting access. Historical sale-line cost is omitted without `reports.view`; inventory movement cost follows the cost-access rule. The React product table also omits its cost column for unauthorized readers. Dashboard receipt DTOs never contain customer contact snapshots, cost fields or other staff's private sales in personal scope.

## Routes and response contract

All routes use the existing `/api/v1` session/CSRF conventions. The active tenant comes from the verified session. A supplied `tenant_id`, actor ID or `branch_ids` cannot select another data scope. A single `branch_id` must belong to the server's allowed branch set.

| GET route | Purpose |
| --- | --- |
| `me/context` | Own memberships, active tenant, effective roles/permissions, allowed branches, widget capabilities and separate platform permissions |
| `platform/context` | Explicitly authorized platform identity/permissions only; no tenant financial data |
| `dashboard/summary` | Authorized sales/current operational summaries and capability flags |
| `dashboard/sales-trend` | Tenant-local daily invoice/return series, including days with verified zero activity |
| `dashboard/branch-performance` | Server-paginated permitted branch sales and real report links |
| `dashboard/top-products` | Bounded SKU ranking by recognized sales excluding tax, after posted returns |
| `dashboard/recent-sales` | Server-paginated safe receipt headers |
| `dashboard/recent-sales/{id}` | Safe receipt header with the same tenant/branch/actor checks; direct detail is not restricted to the list's date range |
| `dashboard/alerts` | Current scoped stock exceptions, critical first, and authorized approval counts |
| `dashboard/approvals` | Current actionable document counts and up to five records per type, with real source API links |
| `dashboard/shift-summary` | Current permitted open sessions and their invoice totals; held carts unavailable |
| `dashboard/inventory-summary` | Recorded SKUs, SKU-location exceptions and quantities grouped by base unit |
| `dashboard/purchasing-summary` | Current PO status counts, suppliers on orders and permitted outstanding payables |
| `dashboard/accounting-summary` | Recognized sales, posted expenses, external/exchange flows, current due balances and closed-shift variance count |

Dashboard query parameters:

- `from`, `to`: strict `YYYY-MM-DD`, inclusive tenant-local calendar dates; defaults to the last 30 days. Maximum 366 days including both endpoints.
- `branch_id`: optional, server-authorized branch; omission means all permitted branches, not all branches in the database.
- `page`, `per_page`: paginated collections; `per_page` maximum 50, default 20. First page loads by default.
- `limit`: bounded rankings/stock exceptions, default 10 and maximum 20.
- `compare=1`: sales summary/trend may include the immediately preceding equal number of local calendar days. A zero comparison baseline produces an unavailable percentage, not an invented growth rate.

The response is `{ data, meta }`. Metadata includes allowed/selected branch IDs, sales scope, currency and scale, timezone, reporting dates, start-inclusive/end-exclusive instants, generated timestamp, data revision and cache freshness. Data is an explicit allowlist DTO through `DashboardResource`, not an entire model. Error responses retain the existing JSON contract. No client-side pagination or first-page aggregation supplies dashboard totals.

Inventory, purchasing, approval and open-shift counts are **current snapshots**; their `basis` describes this distinction from period financial figures. Stock alerts include supported source API filters. Later UI phases must consume those filters and build the corresponding drill-down interactions; no nonfunctional alert widget has been mounted in D2.

## Financial definitions

The original posting services and invoice amounts remain unchanged. Dashboard totals reconcile with the existing report's stored-value rules:

1. Original invoices count by `sold_at`, including invoices later voided. Posted return/cancellation credits count by `posted_at`, including returns of older invoices. Unposted returns have no financial effect.
2. `net_sales_including_tax = invoiced_sales - return_credits`; `net_tax = tax_charged - returned_tax`; `net_sales_excluding_tax = net_sales_including_tax - net_tax`.
3. Do not exclude an original voided invoice and then also subtract its cancellation credit. `invoice_count` explicitly counts original invoices; posted cancellations and voided original invoices have separate counts. Average invoice value is the original invoiced total divided by invoice count, before return credits.
4. Partial/damaged returns reverse the original stored allocations. Damage affects stock disposition, not whether its posted financial credit is recognized. Top products and daily/branch totals use the same posted-return rules.
5. Collections, refunds and posted expenses use their recorded/posting time, including older invoices. Exchange credit is reported separately from external collections. Cash collection is not recognized sales revenue.
6. Receivables/refunds due are **current lifetime balances**, not period income. Current open-shift invoice totals are separate from both the period series and cash collections. A manager's sale in a permitted shift is included unless an explicit personal-sales restriction excludes it.
7. Different currencies are not added: mixed-currency invoice reporting fails closed. Different stock units are grouped separately. Stock exception counts mean SKU-location pairs; low stock excludes stockouts, and an unset threshold is unavailable.
8. Verified gross profit, net profit, bank balances, held carts, transfers and expected/overdue deliveries remain unavailable where supporting modules/data do not exist. No estimated margin is relabeled as verified profit.

Money is returned as decimal strings. MariaDB/MySQL uses native DECIMAL SQL aggregates; SQLite tests use BCMath-backed aggregate functions. Charts/percentages are computed from stored decimal amounts on the server. Tenant-local midnight boundaries are calculated in PHP and applied as half-open storage-time intervals, including DST; daily SQL buckets do not depend on installed database timezone tables.

## Cache and performance

Cache keys include the contract version, tenant, actor, all effective permissions, explicit policy, allowed branches, selected branch, dates, timezone/storage timezone, currency, comparison and pagination settings, endpoint/detail ID and tenant audit revision. This intentionally does not share private cache entries between actors. Membership/permission/branch checks happen before cache access. Responses use `Cache-Control: private, no-store` and `Vary: Cookie` for browser/proxy handling.

The default server TTL is 30 seconds, configurable up to 300; zero disables caching. Successful audited application writes advance the tenant data revision. Direct SQL/backfills which do not create an audit event become visible after TTL expiry; operators can disable the dashboard cache during such work. Generated timestamps describe the cached snapshot. Role/scope changes change the fingerprint immediately and suspended membership is refused even with a warm cache.

Queries aggregate in SQL, use bounded date ranges, paginate branch/receipt collections and cap other lists. D2 adds date/branch/actor indexes. The volume regression test grows a one-invoice fixture to 101 invoices and verifies query count stays bounded, rather than issuing one query per invoice. Dashboard requests have a separate 60-per-minute user/tenant limiter within the existing API limiter.

## Configuring access

Existing Team screens continue to edit the primary role. D2's additional-role/branch policy management is an authenticated API foundation, not a new Team UI.

An unrestricted member with `team.manage` can read `GET team/{membership}/access` and update `PATCH team/{membership}/access` with:

```json
{
  "branch_scope": "assigned",
  "branch_ids": [],
  "sales_scope": "own",
  "additional_role_ids": []
}
```

This example intentionally assigns **no branches**, so it grants no branch data. Supply actual permitted IDs to assign branches. For existing tenant-wide behavior use `branch_scope: "all"`, an empty `branch_ids` array and `sales_scope: "all"`. Additional-role IDs replace the optional additional-role list; they do not replace the primary role. All IDs are tenant-validated; delegates cannot grant permissions they do not hold. Owners cannot be restricted through this API. Changes are audited, and restricted staff cannot widen their own scope.

Platform provisioning is separate, explicit and audited:

```powershell
Set-Location 'POS backend'
php artisan platform:permission grant <existing-user-id> --operator="Your operator identity"
php artisan platform:permission revoke <existing-user-id> --operator="Your operator identity"
```

No command above was run against the working database during implementation. Command lifecycle tests use SQLite in memory. D6 still supplies platform dashboard screens and platform aggregates; a platform grant never creates tenant membership.

## Deployment and verification

Optional environment configuration, with no secret values:

```dotenv
DASHBOARD_API_ENABLED=true
DASHBOARD_CACHE_SECONDS=30
```

Apply only the additive D2 migration after the existing phases:

```powershell
Set-Location 'POS backend'
php artisan migrate --path=database/migrations/2026_11_03_160000_create_dashboard_access_foundation.php
```

It creates five access/audit tables, adds nullable access fingerprints to idempotency/export records, and adds reporting indexes. It does not backfill grants, alter existing amounts or seed users. For rollback, disable the dashboard API/UI while retaining D2's access enforcement and tables. Once restrictions have been assigned, an older authentication build that ignores them is not a safe rollback target. The migration deliberately refuses destructive schema rollback.

Verified on 2026-11-03:

- Full Laravel suite: **117 tests passed, 2,207 assertions**, including **32 dashboard foundation tests**. Coverage includes real cross-tenant/branch requests, explicit zero-branch and personal restrictions, multi-role unions, platform grant/revoke/login isolation, receipt/cost projection, stale permissions/cache separation, posting/idempotency/export boundaries, safe delegation, legacy Blade bypass, tax/discount/credit/return/cancel reconciliation, missing metrics, pagination, bounded query counts, DST and comparison dates.
- Full React suite: **173 tests passed across 15 files** (`npm.cmd test -- --testTimeout=15000`), including a new stale-payload cost visibility regression test. Production build passed.
- The exact additive migration was previewed, reviewed and applied locally. [Preservation evidence](audit/dashboard-d2-migration.json) matched **98 original tables**, existing role grants and `.env`. No existing access policy was changed; the new platform-grant and membership-policy tables remain empty.
- All **11 dashboard service operations** executed successfully using the actual configured MariaDB/MySQL driver in a read-only transaction: [SQL smoke evidence](audit/dashboard-d2-sql-smoke.json). No fake transactions were inserted into the working database.
- Chrome fixture checks passed, including authorized cost visibility, catalog-only reader cost omission even with a stale payload, retained retail prices and all existing D1 responsive/focus checks. [Authorized view](design/d2/d2-product-cost-authorized.png) and [reader view](design/d2/d2-product-cost-reader.png) were visually inspected. These screenshots use synthetic records and do not connect to Laravel or the working database.

The [consolidated verification report](audit/dashboard-d2-verification.json) links the automated, browser, native-driver and preservation evidence.

Automated tests use the existing SQLite `:memory:` fixtures and never rebuild the working POS database. Native-driver smoke checks, fixture screenshots and before/after fingerprints are separate from manual UAT. No staging server has been provided; production/HTTPS acceptance remains pending.

Suggested manual acceptance checks: sign in as an unchanged owner; inspect `me/context` and the dashboard endpoints; assign a test staff member one permitted branch using the access API; verify other-branch list/detail/write/export requests are denied; assign no branches and confirm empty branch data; add another role and confirm it does not widen the explicit scope; suspend the member and retry a warm cached URL; compare period sales/returns with the existing report; verify a catalog-only reader cannot see or export costs. Use dedicated test memberships and do not mark working-data UAT passed based on fixture results.

Implementation entry points: `app/Domain/Dashboard/`, `app/Domain/Tenancy/{TenantContext,RecordScope,PlatformAccess}.php`, the dashboard/context/member-access controllers, `DashboardResource`, access middleware and the D2 migration. Official implementation references: [Laravel 11 cache](https://laravel.com/docs/11.x/cache), [PHP SQLite aggregate registration](https://www.php.net/manual/en/pdo.sqlitecreateaggregate.php).
