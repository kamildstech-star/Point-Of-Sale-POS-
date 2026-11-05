# UX-4 — Capability-based dashboard refinements

**Date:** 2026-11-05 · **Scope:** UX-4, following the user's approval of UX-3.  
**Source plan:** [UI/UX refinement plan, §10 UX-4](UNIVERSAL_POS_UI_UX_REFINEMENT_IMPLEMENTATION_PLAN.md).  
**Visual review:** [Before/after gallery](design/refinement/ux4/index.html).  
**Machine-readable evidence:** [Verification report](audit/refinement-ux4-verification.json).

## Audit findings and architectural fit

The Laravel source is authoritative: `DashboardCapabilities`, `TenantContext`, `TenantRoles`, `RecordScope` and `DashboardService` resolve effective permissions, branch assignments and personal-sales restrictions. Multiple tenant roles already contribute a union of permissions. Numeric role IDs and client-visible role names are not authorization rules.

Before UX-4, the React loader recognized only owner, admin, manager and cashier presentation profiles. An inventory clerk, accountant, viewer or custom role received a generic welcome page even when the server already offered authorized summary data. Resource selection and rendering also repeated their capability checks separately.

The existing summary API already provides stock counts, quantities grouped by unit, purchase order counts, supplier payables, recorded accounting flows and current balances. UX-4 uses those existing contracts. It adds no financial calculations, database migrations, new roles or permission grants.

The separate dashboard D5–D7 plan includes broader specialist/platform workflows and release work. This refinement exposes the summaries already supported; it does not represent completion of every item in those later phases. Verified COGS/gross profit, net profit, bank balances, stock transfers and expected delivery metrics still lack the required backend support. No estimates replace them. The existing separate platform console remains in place.

## Dashboard composition

| Verified presentation/access | Dashboard behavior | Requested dashboard resources |
| --- | --- | --- |
| Owner | Existing business performance, branch comparison, product performance, exceptions and permitted actions | Summary, sales trend, branch performance, top products, stock alerts, each gated by permissions and capabilities |
| Admin | Existing sales, open shifts, open purchase orders, stock exceptions, approvals and administration tools | Same business resource family, gated individually |
| Manager | One permitted branch, branch sales, actionable approvals, scoped staff activity and receipts | Summary, trend, receipts, staff activity and stock alerts, where authorized |
| Cashier | Personal open shift, prominent New Sale, real held carts and own receipts | Cashier summary only; no company dashboard payloads |
| Inventory access | Recorded SKUs, low stock, stockouts, draft counts, actionable alerts and quantities by unit | Summary and stock alerts; additional sales trend only if granted |
| Purchasing access | Open/draft orders, suppliers on permitted orders and outstanding payables | Summary; other permitted modules compose into supporting indicators |
| Accounting/report access | Recorded collections, expenses, current receivables/refunds due and permitted sales trend | Summary and sales trend; no unused branch/product/receipt requests |
| Sales access without a recognized profile | Own/permitted sales, invoice counts, trend and permitted operational tools | Summary and sales trend, where authorized |
| Viewer / no metric grants | Honest “No dashboard metrics available” state and permitted workspace tools | Context only; no dashboard metric requests |
| Custom / multiple roles | Combined modules from effective grants with a deterministic capability-based ordering | Only resources used by the resulting view |

Owner/admin/manager/cashier profile codes choose the established presentation. Each widget still requires a matching server capability **and** effective permission. Other layouts prioritize accounting capability, inventory adjustment access, purchasing access or sales capability. Unknown role names or changed numeric IDs do not expand access.

## Implementation

- `dashboardRegistry.js` defines widget capability/permission requirements, sources and layout ordering. The loader and dashboard renderers share its resolved plan.
- `useBusinessDashboard.js` refreshes verified context before planning requests. It deduplicates shared summary requests and retains cancellation, scope metadata checks, consistent data revision checks and fail-closed authorization handling.
- `CapabilityDashboard.jsx` composes specialist/custom views and a tools-only viewer state. Reporting dates appear for period metrics; stock/purchasing-only dashboards explicitly describe current snapshots.
- `DashboardWidgets.jsx` shares approval lists, stock alerts, summary indicators, quantities by unit and recorded collection/refund breakdowns. The existing business and manager views use the same approval and alert widgets.
- `WorkspaceOverview.jsx` loads the appropriate dashboard lazily. Missing capability support and existing feature kill switches retain the legacy welcome fallback.
- `dashboard.css` applies the existing compact tokens to the new layouts. Inter, Remix icons, theme, sidebar, control dimensions and checkout density remain consistent.

No new dependency was installed. All production work is JavaScript/JSX and CSS. Existing route and API contracts are preserved.

## Data and interaction details

Amounts come from server decimal strings. Display formatting does not coerce them through JavaScript `Number`. Stock quantities remain grouped by unit; EA and KG are not added. Counts distinguish recorded SKUs from SKU-location pairs and suppliers on orders from the supplier directory.

Unavailable values display as unavailable rather than zero. An unset low-stock threshold has no misleading filtered link. Draft stock counts have no invented status-filter link because the inventory page does not consume that filter. Posted expenses remain a period metric without a misleading period-filter link to the expense list; the separate Expenses tool opens the supported branch-scoped page.

Selected branch IDs are carried into supported operational drill-downs. Cashier routes keep personal/shift context. Manager receipt links now retain `own=1` when the verified sales policy is personal. Direct destinations still reauthorize on the server.

All scope changes clear the previous view before reloading. Tenant ID, access fingerprint, branch IDs, dates, currency, timezone, sales scope and data revision are checked before results are combined. A 401/403/419 stops the complete view; a non-authorization failure of a secondary widget keeps its local retry state. Refresh resolves permissions again.

## Verification

The final totals and source hashes are recorded in [the verification report](audit/refinement-ux4-verification.json). **263 React tests passed across 23 files**, including **43 focused dashboard tests** across three files. All three dashboard test files in the full run executed after the final application edits. The production build passed. Final Chrome verification passed **144 checks** with **42 current screenshots** and **36 actual baseline screenshots**, covering nine profiles at 360, 768, 1280, 1440 and 1920px plus 200% zoom.

Fresh Laravel regression: **48 tests passed, 1,161 assertions**, across `DashboardFoundationTest` and `StaffDashboardTest`. Tests use forced in-memory SQLite, an explicit testing key, array cache and synchronous test queues. They cover actual second-tenant records, assigned branches, no assigned branches, multi-role grants, personal-sales policies, authorization revocation, warm cache boundaries, real drill-down contracts and cashier/manager behavior. PHPUnit emitted a permission warning while writing its optional result cache; the tests and assertions passed.

Chrome checks use a local HTTP fixture server serving the production build. All records and accounts in screenshots are synthetic. The browser harness checks layout titles, allowed resource requests, permission-limited viewer/cashier behavior, branch drill-downs, contrast, mobile reflow and 200% zoom. Historical UX-1/2/3 galleries are preserved. No working POS database records were created, modified or deleted.

### Reproduce locally

From `POS Frontend`:

```powershell
npm.cmd test -- --testTimeout=20000
npm.cmd run build
node scripts/refinement-ux4-browser-smoke.mjs
node scripts/record-ux4-verification.mjs
```

From `POS backend` (PHPUnit configuration forces an isolated in-memory database):

```powershell
& 'D:\xamp 8.2.12\php\php.exe' artisan test --filter='DashboardFoundationTest|StaffDashboardTest' --do-not-cache-result
```

Do not rerun the browser harness with `--before` against the new build: that option was used to capture the actual UX-3 baseline before rebuilding UX-4.

## Manual review scenarios

1. Sign in as an inventory clerk. Check stock exceptions before secondary tools, current-snapshot labels and quantities separated by unit. Open a stock alert and verify its location/SKU scope.
2. Select a different allowed branch. Every displayed summary and stock drill-down should use that branch. A restricted account cannot select an unassigned branch.
3. Use purchasing-only test access. Check open/draft counts and payable display; there should be no sales, accounting or invented delivery chart.
4. Use accountant access. Check period dates and currency, distinguish external collections from exchange credit, and distinguish current balances from period expenses. Profit/bank estimates must not appear.
5. Use a viewer. Only permitted tools should appear, with an explicit no-metrics state and no dashboard data requests in the network panel.
6. Use multiple/custom roles. Confirm all effective permitted modules compose into the view; changing role IDs/names must not create grants.
7. Refresh after revoking a grant in a test business. Old figures disappear and the new permitted composition loads. An authorization refusal must not leave part of the old view visible.
8. Compare owner/admin/manager/cashier workflows with the baseline gallery. Cashier should retain a prominent New Sale and personal receipt/held-cart links; manager should retain a single permitted branch.
9. Review 360px desktop-emulated width, dark mode, keyboard focus and 200% zoom. Wide quantities stay inside the shared table viewport.

## Review boundary

UX-4 was approved and the user explicitly authorized [UX-5 accessibility/performance refinements](UX5_REFINEMENT.md). Its audit, changes and evidence are recorded separately. New persisted preferences, an action center and broader specialist/platform backend requirements are not part of that approved slice.
