# Role-based dashboards — Phase D0 audit and proposed scope

Reviewed 2026-11-02 against the [supplied specification](UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md) and the reference image attached in the conversation.

**Status update, 2026-11-03: D0–D4 implemented following their separate approvals.** D1's shared shell and reusable components are documented in [DASHBOARD_D1.md](DASHBOARD_D1.md). D2 preserves existing access with explicit branch restrictions; its server context, role unions, scoped APIs and verification are documented in [DASHBOARD_D2.md](DASHBOARD_D2.md). D3 owner/admin views are documented in [DASHBOARD_D3.md](DASHBOARD_D3.md). The subsequently requested D4 adds cashier/manager views and real Hold/Recall in [DASHBOARD_D4.md](DASHBOARD_D4.md). D5–D7 remain separate pending scope. The findings and proposals below preserve the original D0 review as written.

**Subsequent UI refinement, 2026-11-05:** the separately approved [UX-4 refinement](UX4_REFINEMENT.md) adds a capability registry and specialist/custom summary views using the APIs already implemented in D2. Inventory, purchasing and accounting summaries now have useful React compositions. This does not complete the broader D5–D7 specialist/platform and release requirements below.

**Latest scope, 2026-11-05:** the user's combined D5–D7 request authorizes the remaining dashboard engineering. [DASHBOARD_D5_D6_D7.md](DASHBOARD_D5_D6_D7.md) records the confirmed backend sources, implemented specialist/personal/platform projections, tests, screenshots and pilot/rollback controls. D5/D6 engineering and D7 local QA/rollout preparation are implemented; staging, physical-device acceptance, pilot feedback and human role/release sign-off remain pending. New specialist/platform pilots default off. The earlier status paragraphs and D0 findings below are historical.

The original D0 pass changed no application code, dependency, environment, migration, permission grant, customer record or transaction. It saved only the supplied specification and this audit. No live database, credentials or Stripe account was accessed; schema findings come from source/migrations and prior implementation documentation, not a fresh live-schema audit.

The specification's section 13 says: “Then implement each phase sequentially, seeking approval before expanding scope.” Its D0 acceptance requires review of the widget/data map. This document makes that review concrete before D1 begins.

## 1. Existing architecture and reusable foundations

The original [AGENT.md](AGENT.md) is explicitly a historical Blade audit. Current behavior is described by [IMPLEMENTATION.md](IMPLEMENTATION.md), [PHASE_2_3.md](PHASE_2_3.md), [PHASE_4_5.md](PHASE_4_5.md), [PHASE_6_7.md](PHASE_6_7.md), [UI_FOUNDATIONS.md](UI_FOUNDATIONS.md) and [GLOBAL_LOADING.md](GLOBAL_LOADING.md). The new **D0–D7 dashboard phases** are separate from the earlier backend phases and UI-0–UI-7 design track.

| Area | Current implementation | Dashboard implication |
| --- | --- | --- |
| Frontend | React 18.3, JS/JSX, React Router 6, installed Vite 6.4.4 | Keep existing routes and JavaScript; no TS/TSX |
| Styling | Tailwind 4.3.3 through Vite, semantic CSS, Inter 5.3.0 served locally | Reuse; Preflight remains omitted to preserve native controls |
| Components | Local JavaScript **shadcn-style** primitives, not a generated shadcn/Radix installation | Reuse native foundations; do not run a TS/Lucide-generating installer |
| Icons | Existing named Remix registry and `Icon` wrapper, package 4.9.0 | Reuse; no second icon library |
| Theme | One `ThemeProvider`, light default, per-user/browser preferences, dark root class | Extend existing tokens; do not add another provider |
| Feedback | Global request progress, regional skeletons, Toastify, retryable errors | Reuse timing, concurrency and modal-accessibility handling |
| Dialog/forms | Focus-managed `Modal`/`FormDialog`, native validated controls, `SaveForm`, `LookupSelect`, same add/edit forms | Preserve focus, summaries, decimal strings and submission locking |
| Tables/actions | `RemoteTable`, server pagination, `RecordActions`, confirmation dialogs | Reuse; never derive a tenant KPI from one downloaded page |
| Shell | Inline `Layout` in `src/app/App.jsx`; permission-filtered links, tenant name/user/theme/logout | Extract into reusable layout components after D1 approval |
| Sidebar | Menu-height desktop sidebar capped to viewport, fixed on both page-scroll axes; mobile sticky horizontal links | Preserve desktop pinning/content height; approved D1 can add collapsed/drawer presentation |
| Dashboard | `/` renders a welcome banner and status cards, not analytics | Preserve until real dashboard APIs/variants are approved and tested |
| Charts | No React chart dependency or reusable chart implementation | Create one shared accessible chart contract; choose implementation in D1, not nine unrelated chart stacks |
| Backend | Laravel REST API, session cookies/CSRF, domain services and tenant query helpers | Keep server authorization and monetary calculations authoritative |
| Legacy | Blade admin/storefront and historical dashboards still separate | No Blade replacement or legacy-data import in dashboard design work |

The new guide's dark hex values differ slightly from the already implemented guide. Keep the existing semantic token names and accessible text/action variants; reconcile any palette refinements centrally in D1. Do not create parallel `--surface`/`--app-card` theme systems. The attached mockup's amounts, dates and role names are visual examples, not production fixtures. Its inline image has been reviewed; the attachment directory contains only the text file, so no local PNG was available to copy into the suggested reference path.

## 2. Authentication, tenant, role and branch findings

### Existing authorization

- `ResolveTenant` reads `active_tenant_id` from the session and verifies active membership through `TenantContext::resolve`. API tenant IDs are not trusted as an authorization source.
- `tenant_memberships` has one `role_id` and a unique `(tenant_id, user_id)` constraint. One person can belong to multiple businesses, but **multi-role membership within one business is not implemented**.
- `TenantContext::permissions()` reads that membership role's explicit permission rows. `TenantPolicy` and controller/domain permission checks enforce these capabilities server-side. The frontend receives role code/name, permissions and active tenant through `StaffResource`.
- System role templates are `owner`, `admin`, `manager`, `cashier`, `inventory_clerk`, `accountant`, `viewer`. Custom roles are supported. Templates preserve established permission changes rather than overwriting them at every seed.
- `dashboard.view` is the existing shell capability. There are no current `dashboard.platform.view`, `dashboard.business.view`, `finance.view`, `shift.view_own` or role-aware dashboard-widget capabilities.
- Existing capabilities use `purchases.view/manage/approve/settle` and `team.view/manage`; the new guide's illustrative `purchasing.*` and `users.manage` keys are not drop-in replacements.
- The legacy numeric Super Admin role and tenant owner are **not** a platform-administrator security model. Neither may be automatically promoted to platform access.

### Branch and personal scope gaps

Branches and locations belong to tenants. `GET /api/v1/context` currently returns the tenant's branches/locations, and tenant-bound validation verifies a branch belongs to that tenant. No per-user branch-assignment table/policy was found in the inspected implementation. A branch query parameter is therefore a reporting filter, not an assigned-branch authorization boundary.

`cash/sessions` is already restricted to the current membership's opened shifts unless `cash.manage` is granted. Cash reconciliation also validates the shift actor or management capability. However, `GET sales` under `sales.view` lists tenant-wide records; it does not apply an own-cashier/own-shift filter. A cashier profile must not call this endpoint and merely hide other staff's sales in React.

Sales store `cashier_membership_id`, shift and branch IDs, so server-side personal/shift aggregation is feasible. They do not store a separate assisted-selling associate attribution/customer-assignment/follow-up workflow. Existing API resources often expose record fields under broad module permissions; the proposed cost/profit/PII distinctions need explicit server-side field selection, not CSS hiding.

**D2 requirement:** approve role templates, multi-role rules and any branch-assignment schema first. Then enforce branch/actor restrictions across relevant dashboard queries, details, drill-downs, exports and cache keys. A dashboard restriction alone cannot establish secure branch isolation if linked operational endpoints remain tenant-wide.

## 3. Proposed profile and widget/data map — awaiting review

Every row remains capability-driven: a role label selects presentation defaults, never additional access. Existing explicit grants must be preserved. Custom roles receive only widgets authorized by the backend; do not force them into a named role with broader rights.

| Requested profile | Current match | Initial usable sources/actions | Missing or deferred |
| --- | --- | --- | --- |
| SaaS Super Admin | No platform role/guard/API | Existing Stripe records are tenant billing only | Separate platform authorization, aggregate APIs, tenant/subscription growth, incidents/support policy; MRR definition and currency handling |
| Business Owner | `owner` | Reports summary with `reports.view`, inventory/alerts, sales/purchases, business/team/billing controls | Dashboard aggregates, daily series, branch comparison, top products, comparison periods; verified gross profit unavailable |
| Business Admin | `admin` | Existing operational modules subject to effective permissions; approvals through dedicated capabilities | Scoped aggregate open-shift/order/exception counts and dashboard profile contract; billing/legacy management are not implied |
| Branch Manager | `manager` is a broad tenant role, **not** an assigned-branch role | Module functionality exists | Branch assignments and enforced scope, staff activity aggregates, branch-only metrics/approvals; do not claim this profile is currently secured |
| Cashier | `cashier` | New Sale, own open shifts, allowed shift reconciliation and customer/product lookup | Own-shift sales/order/item aggregates and recent receipts endpoint; company profit hidden; hold/recall unsupported |
| Inventory Manager | Closest template: `inventory_clerk` | Balances, movements, reservations, counts, adjustments; approvals only with `inventory.approve` | Aggregate current low/out-of-stock counts, movement series and cost visibility rules; transfers unsupported |
| Purchasing Manager | No dedicated template; a custom role can hold `purchases.*` | Orders, receipts, supplier directory, payables/settlements with corresponding permissions | Status/count/spend aggregates, expected/overdue delivery metrics and dedicated template review |
| Accountant | `accountant` | Reports, expenses, receivables/refunds due, supplier payables, authorized reconciliation | Income/expense series; bank balances/general ledger/net profit unavailable; verify cash capabilities before showing shift controls |
| Sales Associate | No dedicated template or assigned-customer workflow | Product/customer lookup or New Sale only if explicitly granted | Personal attribution beyond cashier, own-sales API, assigned customers/follow-ups and role template |
| Optional Support Agent | Tenant support cases exist, platform support role does not | Tenant case list/create under `support.manage` | Platform support access, auditing/assignment policy and aggregate workflow; no fabricated support dashboard |

Suggested rollout order: shared foundations → dashboard scope/API foundation → owner/admin → cashier and securely assigned branch managers → inventory/purchasing/accounting → platform and sales associate. Unsupported widgets stay absent or explicitly unavailable with explanatory text. Never show a numeric zero when data is unavailable.

## 4. API and widget inventory

Paths below are relative to `/api/v1`.

| Widget/capability | Existing source | Reuse/gap |
| --- | --- | --- |
| Identity/effective permissions | `auth/me` (`me` alias), `StaffResource` | Reuse server-resolved user/tenant; no frontend role-ID authorization |
| Business switcher | `GET tenants`, `POST tenants/{id}/activate` / `tenant/switch` | Reuse verified memberships, session/CSRF rotation and outlet reset |
| Context/branches | `GET context`, catalog branches/locations | Tenant scope only; proposed `me/context`/allowed-branch resource requires D2 |
| Period financial totals/counts | `GET reports/summary?from&to&branch_id`, `reports.view` | Real source exists; no narrow role-specific dashboard response or trend series |
| Recent sales/receipt | `GET sales`, `sales/{id}`, `sales.view` | Paginated/detail sources exist; no personal/branch dashboard scope, period filters or sensitive-field allowlist |
| Shift status/reconciliation | `GET cash/sessions`, `cash/sessions/{id}/reconciliation`, `cash.open` | Own-shift behavior exists; collection counts are payment rows, not automatically sale count |
| Inventory | `GET inventory/{balances,movements,counts,adjustments,reservations}`, `inventory.view` | Paginated records/filtering exist; add aggregates/date buckets rather than downloading all pages |
| Low-stock alerts | `GET workspace/alerts`, `alerts.view`; posting-time low-stock events | Historical notifications are not the current unique low-stock-SKU count; threshold may be unset |
| Purchasing | `GET purchasing/{orders,receipts,returns,payables,settlements}`, `purchases.view` | Real records; no dashboard aggregate or expected-delivery date in current PO schema |
| Suppliers/customers | `GET operations/suppliers`, `operations/customers` | Searchable tenant directories; no assigned-customer/follow-up profile |
| Expenses/reconciliation | `expenses`, reports summary, authorized cash reconciliation | Real accounting-related figures; no complete bank ledger, verified COGS or net-profit report |
| Approvals | Inventory/purchase/return/expense action endpoints with separate grants | No unified pending-approval feed or consistent cross-module drill-down/filter contract |
| Notifications | `workspace/alerts`, mark-read endpoint | Normal HTTP pagination; no live WebSocket stream, global unread aggregate or platform notification service |
| Billing | `billing/plans`, `subscription`, `usage`, `invoices`, hosted Checkout/Portal | Tenant billing only; Stripe live charging defaults disabled and remains outside dashboard work |
| Global search | Per-module search exists | No cross-module business-record search API; a D1 navigation search must clearly be navigation-only |
| Platform metrics | No `/platform/dashboard` or corresponding API | Do not reuse tenant reports to synthesize platform visibility |

None of the specification's `/dashboard/*` endpoints currently exists. The suggested endpoint names are proposed contracts, not routes ready for frontend wiring. Server pagination remains the rule for lists; first-page totals cannot substitute for filtered dashboard aggregates.

Source detail: inventory movements carry stored cost fields and current broad read endpoints do not implement a distinct valuation grant. Purchase order records have status, location, supplier, amount and timestamps, but no expected-arrival date. Sales record the cashier membership; no hold/recall sale document exists. Inventory reservations are not held carts, and receipt timestamps cannot establish a promised delivery date.

## 5. Financial definitions and performance prerequisites

Current `ReportService` is the reference to reconcile against, not the mockup:

- Invoiced sales sum stored `grand_total` at `sold_at`; return credits use **posted** returns at `posted_at`.
- `net_sales_including_tax = invoiced_sales - return_credits`.
- `net_tax = tax_charged - returned_tax`; `net_sales_excluding_tax = net_sales_including_tax - net_tax`.
- Net external collections are recorded external payments minus recorded external refunds. Exchange credit is reported separately. Cash collected is not interchangeable with recognized revenue.
- Expenses are posted expenses in the selected posting period. Current receivables/refunds due are **lifetime balances**, not period revenue.
- `estimated_gross_margin` uses stored sale-line cost estimates, with returned cost treatment based on disposition. It is neither verified COGS nor net profit. Hide the mockup's “Gross Profit”/“Net Profit” KPI until an approved reliable definition exists, or label the supported estimate precisely for authorized users.
- `sale_count` currently counts sale records in the reporting period, including original invoices subsequently voided. A void posts a return credit while retaining the original sale. Do not simply exclude voided sales and also subtract their credits; define completed transactions/order count and cross-period cancellations with fixtures first.
- Report dates use tenant timezone and a half-open interval from local start-of-day through the day after `to`; current validation limits the interval to 366 days. Current server storage/query timezone conversion must be retained.
- Keep backend BCMath/decimal strings authoritative. Format tenant currency in React without using floating-point chart values to calculate accounting totals. Quantities with different units must not be added into a misleading “items in stock” total.
- No comparison-period or time-series response exists; omit trend arrows/percentages until a like-for-like backend comparison is supplied. Tenant currency does not authorize cross-currency platform aggregation.

The existing financial report locks the tenant row in a transaction and iterates sales/lines/balances. It is not designed for frequent multi-widget polling. D2 should provide bounded aggregate queries, query-count tests, date/location indexes where justified, and a documented freshness policy. Cache keys must include tenant, effective allowed branch scope, actor when personal, capabilities/field policy, date range, timezone/currency and version. Recheck authorization before cache access; do not rely on a tenant-only cache key for branch/private widgets.

## 6. Proposed D1 implementation — approval requested

This next phase would implement shared visual foundations and the responsive shell **only**, reusing real existing features. D2 APIs, new permission models/schema and D3–D6 dashboard metrics are separate approvals.

### Exact proposed files/areas

| Files | Planned change |
| --- | --- |
| `POS Frontend/src/app/App.jsx` | Extract protected Layout into a reusable tenant layout; retain all existing route paths, guards and outlet tenant keys; keep current overview until dashboard data is approved |
| New `src/layouts/TenantLayout.jsx` | Compose shared shell; keep platform shell out of tenant context |
| New `src/components/layout/AppSidebar.jsx`, `Topbar.jsx`, `TenantSwitcher.jsx`, `NavigationDrawer.jsx` | Permission-filtered existing routes, real membership switching, responsive collapse/drawer, keyboard/focus handling, existing sign-out/theme |
| New `src/components/dashboard/{PageHeader,KpiCard,ChartCard,DateRangeFilter,QuickActionGrid,AlertList,EmptyState}.jsx` | Reusable JS components with explicit loading/unavailable/error states, capability-gated real links and data passed through props; no invented metrics |
| Existing `src/components/ui/primitives.jsx`, `src/styles/tokens.css`, `foundations.css`; new shell/dashboard styles | Extend current semantic theme and responsive rules; preserve form/table/dialog contracts and desktop sidebar pinning/content-height policy |
| Existing theme, icons, `LoadingState`, `GlobalLoader`, `RemoteTable`, `ErrorNotice` | Reuse; add named Remix entries only if an actual component requires them |
| New focused shell/dashboard component tests and docs | Permissions, switching context, drawer/Escape/focus, empty/error/loading, both themes and real-link behavior; before/after screenshots at 360/768/1280/1440 widths |

D1 constraints:

1. Business switcher uses existing verified memberships. Branch/date components can be built/tested, but render only where an actual data source consumes them. No global branch selector implying assigned-branch security before D2, and no filter that leaves figures unchanged.
2. Drawer/collapsed navigation replaces the mobile strip only after D1 approval. Desktop stays fixed during horizontal/vertical document scrolling, menu-height bounded to the viewport with internal overflow.
3. Notifications/help/search are connected to available authorized workflows or omitted. A navigation finder may search permitted destinations; it must not claim to search transaction/customer records. No fake unread counter or inert icon buttons.
4. Reuse JavaScript primitives, one theme provider, Remix and current loading/toast/focus systems. No dependency install is needed for basic extraction; any chart package choice must be explicit and JavaScript-compatible. Do not add live KPI cards before D2/D3 data wiring.
5. Dashboard chart primitives accept supplied verified data and expose an accessible table/summary alternative. Synthetic data belongs only in isolated tests/previews. An unavailable value must never silently render as zero.
6. No backend/schema/role-grant changes, no automatic legacy Super Admin promotion, no live Stripe charging, no financial arithmetic changes. Do not commit the repository's existing moved-file deletions or unrelated work.

## 7. D2 decisions and required verification before role rollout

Separate review is required for multi-role membership and precedence, branch assignments (including what unassigned staff may access), dashboard/finance/cost grants, own-vs-tenant sales, platform administrator provisioning, custom-role widget behavior, and metrics/cancellation definitions. A proposed future permission name is not authorization to seed it or grant it to existing staff.

Before D2 implementation, verify the actual deployment schema/migration state and indexes without changing records. Any new branch/role/platform schema is a substantive missing backend feature, requiring explicit approval and additive backward-compatible migrations; no migration is justified solely by visuals.

Existing source tests cover tenant isolation, custom-role denials, cashier discount/approval restrictions, financial posting/reconciliation and Stripe authorization. Test fixtures assert SQLite `:memory:` through `tests/Concerns/BuildTenantDatabase.php`; they must remain isolated from the POS database. The last implementation pass reported 156 passing React tests and a passing build; **no tests or build were rerun in this documentation-only D0 audit**.

New dashboard acceptance tests must include:

- Own/other tenant and own/other/unassigned branch requests; forged tenant/branch filters; suspended membership; changed permissions; cache separation and invalidation.
- Cashier/personal APIs omit other staff's rows and cost/profit fields; direct detail/drill-down/export requests enforce the same policy.
- Multi-role/custom-role union and explicit restrictions after that model is approved; platform users cannot implicitly read tenant finances.
- Normal sales, partial/full returns, damaged returns, voids, older-invoice refunds, split/exchange tender, tax/discount rounding, credit, posted expenses, unpaid balances and zero/empty/unavailable cases.
- Tenant-local midnight and DST boundaries, comparison-period alignment, decimal/currency formatting, aggregate pagination correctness and query-count bounds.
- Both themes, slow/error responses and stale-response protection, 360/768/1280/1440 widths, keyboard/focus, accessible charts and no dead-end controls.
- Pilot feature flag/rollback preserving the existing overview and all current POS routes. HTTPS staging and product-owner sign-off remain release gates; no staging server has been provided.

## Source evidence

- `POS Frontend/src/app/App.jsx`, `auth/AuthProvider.jsx`, `features/operations/OperationsNav.jsx`, `components/`, `theme/`, `styles/`, `package.json`.
- `POS backend/app/Domain/Tenancy/{TenantContext,TenantRoles,TenantQueries}.php`, `Http/Middleware/ResolveTenant.php`, `Policies/TenantPolicy.php`.
- `POS backend/app/Http/Resources/StaffResource.php`, `Http/Controllers/Api/V1/{TenantContextController,TeamController,PosController,InventoryController,PurchasingController,FinanceController,WorkspaceController}.php`.
- `POS backend/app/Domain/Operations/{ReportService,CashService,SaleService,ReturnService,InventoryPostingService,WorkspaceService}.php`.
- `POS backend/routes/{api,operations,finance-workspace}.php`; migrations `2026_10_19_100000_create_tenant_foundation.php`, `2026_10_19_130000_create_inventory_purchasing_pos.php`, `2026_10_20_140000_create_returns_cash_reports.php`; `config/stripe_billing.php` defaults.
- Existing isolated tenant/operations/return/billing tests and previous architecture/theme/loading documentation linked above.
