# Dashboard D3 — Business Owner and Business Admin

D3 implements the owner and admin views at the existing React **Overview** route (`/`). Its request authorized D3 from the [dashboard specification](UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md). The separately requested [D4](DASHBOARD_D4.md) now adds cashier/manager views; D5–D7 remain pending. The attachment's generic prompt is not treated as approval to implement unrequested phases.

## What is available

| Area | Owner | Business Admin |
| --- | --- | --- |
| Period KPIs | Net sales excluding tax; original invoices; posted return credits | Net sales excluding tax |
| Current operational KPIs | Low stock and stockout SKU-location counts | Low stock, stockouts, open shifts, approved/partially received purchase orders |
| Financial visibility | Current receivables and net external collections when authorized; gross profit explicitly unavailable | Only widgets permitted by the server; no fabricated profit |
| Charts | Daily sales and branch comparison | Same authorized charts |
| Lists | Top five SKUs, current stock exceptions, pending approvals | Same authorized lists, with operational quick actions |
| Shortcuts | New sale, add product, purchase orders, adjustments, reports | These plus shifts and user access where permitted |

Figures and widget requests are controlled by D2's server capabilities, not the selected presentation profile. A restricted admin with no branch assignments sees no branch records. Personal-sales restrictions still override reports/role unions. Unsupported widgets are omitted or explicitly unavailable; verified zero remains zero.

The server chooses `dashboard.profile` from verified tenant role codes: owner precedes admin when both apply. This selects presentation only and grants no access. Other role profiles keep the existing welcome overview until their approved phases. Legacy numeric Super Admin is still independent of platform permissions.

## Filters, requests and consistency

- Reporting defaults to the last 30 calendar dates in the business timezone. The date filter validates order and a maximum of 366 inclusive dates. Branch options come from `me/context`.
- Each scope change clears the previous view before fetching context and all dependent widgets. Canceled/unmounted requests cannot restore old tenant or branch figures. No business ID or actor ID is accepted as a client-controlled dashboard scope.
- The dashboard requests summary, daily trend, branch performance, top products and alerts only when authorized. Summary already contains operational/accounting/approval aggregates; the UI does not download all operational records to count them.
- Dashboard response metadata now includes the tenant ID and access fingerprint. The UI verifies business, policy, branch set, personal scope, currency, timezone and reporting dates before combining responses.
- A data-revision mismatch retries the widget batch once. Persistent mismatches produce a retryable error. A 403 from any requested widget clears the entire view; other optional-widget failures retain successful cards and show an inline retry.
- Refresh reloads access and all widgets. The oldest successful widget timestamp is shown. The server's configured cache age and metric definitions are disclosed under **Metric definitions and freshness**. This is request-driven refresh, not live WebSocket reporting.
- Branch results use server pagination, five branches per page. The chart labels its page scope and never treats that page as the business total. Top products and alerts load at most five records; approval counts are independent of their bounded document previews.

Money remains exact server decimal strings through cards, data tables and display formatting. Only SVG positioning uses floating-point numbers. Branch comparison uses signed bars rather than percentage shares because posted credits can make a branch negative. The charts include accessible exact-value tables.

## Working drill-downs

| Dashboard action | Destination behavior |
| --- | --- |
| Sales, invoices, returns, collections or receivables report | Reports consumes `from`, `to`, `branch_id`, auto-runs the selected report and preserves those filters in its form |
| Branch chart entry | Opens that branch's report for the same period |
| Product ranking | Opens the actual catalog product detail |
| Low stock / out of stock KPI | Inventory consumes `tab=balances`, `branch_id`, `stock_state=low|out`; Laravel applies the same configured threshold and quantity rules |
| Stock alert | Inventory consumes the exact `location_id` and `variant_id`; alert labels are returned through bounded, tenant-scoped catalog/location lookups |
| Stock adjustment/count approval | Inventory selects the matching tab, filters the list by `document_id` and loads the scoped document |
| Purchase order approval | Purchasing selects orders, filters the list and loads that document |
| Open PO KPI | Purchasing filters approved/partial orders in the selected branch |
| Return approval | Existing `/returns/{id}` detail route |
| Expense approval | Expenses filters to the requested document |

Filtered lists disclose applied filters, provide **Clear filters**, retain server pagination and reset incompatible filters when changing tabs. Target APIs validate parameters and retain tenant/branch/personal scopes. Links require the corresponding source-view permission. A shortcut never approves, posts, charges or deletes a record.

## Implementation and rollout

Entry/controller: `POS Frontend/src/features/dashboard/WorkspaceOverview.jsx` and `useBusinessDashboard.js`. Layout: `BusinessDashboard.jsx`. Shared utilities: `dashboardData.js`. The dashboard body is loaded as a separate production chunk. Existing Remix icons, semantic theme tokens, regional/global loaders, form validation, Toastify and D1 components are reused. No new package was installed.

`VITE_OWNER_ADMIN_DASHBOARD_ENABLED` defaults enabled. To restore the welcome overview, set it to `false` in the frontend environment and rebuild/restart Vite. This is a UI rollback only: retain D2 access enforcement, tables and restrictions. No migration, seeder, permission grant, billing configuration or working POS record is changed by D3. The API cache contract version advances so old response shapes are not reused.

Staging remains pending because no staging server has been supplied. Live Stripe charging is unchanged and is not enabled by dashboard work.

## Verification and manual acceptance

Automated backend tests cover D3 presentation/capability separation, signed daily/branch/product/report reconciliation, and scoped inventory/purchasing/document filters. Existing tax, discount, damaged/partial return, cancellation, currency, own-sales and tenant-isolation regressions remain in the full suite.

The new signed reporting fixture has a 90.00 sale on November 2 and a 12.35 posted credit on November 3 for an older invoice in another branch. The three-day net total is **77.65** across summary, daily series, branch sum, product ranking and reports. Existing tax/discount fixtures independently reconcile stored allocations.

React tests cover both profiles, server-only widget availability, date validation, branch refetch, canceled/stale responses, policy/revision mismatches, partial failures/retries, empty branch assignments and actual operational drill-down consumers.

Chrome checks use an isolated HTTP fixture server and the production build. Screenshots in [design/d3](design/d3/) contain synthetic records, not customer data. Test fixture data is not imported by the application. See [audit/dashboard-d3-browser.json](audit/dashboard-d3-browser.json) and [audit/dashboard-d3-verification.json](audit/dashboard-d3-verification.json) for final execution evidence.

Verified on November 3, 2026: **121 Laravel tests / 2,347 assertions**, **191 React tests across 17 files**, production build, and **44 Chrome checks / 20 screenshots**. Following small permission/error-message refinements, all 43 affected React workflow tests passed again; the final report-form refinement also passed its five drill-down tests. Chrome covers both profiles and both themes at 360, 768, 1280 and 1440 pixels, text contrast, exact-value tables, scoped links, retry, loading, revoked access, empty states and business replacement. The real MariaDB read-only SQL smoke passed all 11 dashboard operations. PHP formatting passed for the eight changed PHP files.

Manual acceptance:

1. Log in with an owner membership and open Overview; confirm timezone/currency and authorized KPIs.
2. Compare the same branch/date selection against Reports. Test a sale, partial/damaged return, posted cancellation and return of an older invoice in a test business.
3. Select a single branch, apply another date range and refresh; no figures from the prior scope should remain during loading.
4. Open branch, stock alert, stockout/low-stock and approval links; confirm applied filters and source records. Clear filters and switch tabs.
5. Use an admin membership; verify operational KPIs and user-access/shifts shortcuts subject to permissions.
6. Restrict a test admin to assigned branches, then to no branches, then personal sales. Confirm API refusal for forged branch IDs and no unauthorized financial widgets.
7. Revoke an appropriate widget permission, then refresh; confirm the new permitted view or access error. Do not change production grants merely to run this check.
8. Test no sales, no alerts, unset low-stock threshold, zero prior-period sales and a negative branch total.
9. Simulate a failed optional API and retry; simulate expired/revoked access and confirm all old figures are cleared.
10. Test both themes at 360, 768, 1280 and 1440 pixels, keyboard navigation, date validation, exact chart tables, sidebar/drawer and drill-down links.
11. Confirm cashier/manager/other later-phase roles still receive the welcome overview and keep their existing modules.
12. Optionally test the frontend flag rollback and restore it; keep server access restrictions enabled.
