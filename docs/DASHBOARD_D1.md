# Dashboard D1: shared shell and components

Implemented under the user's **D1-only approval** following the [D0 audit](ROLE_BASED_DASHBOARD_AUDIT.md) and [supplied dashboard specification](UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md). Dashboard phases D0-D7 are separate from the original backend blueprint and the earlier UI design phases.

## What works in the application

- **Desktop navigation:** a 240 px sidebar, collapsible to 72 px. The button at its foot toggles the presentation. Links retain accessible names and tooltips when collapsed. The preference is stored per authenticated user in this browser under `universal-pos:navigation:user:<id>`; it stores no authorization data.
- **Desktop scrolling:** the sidebar stays at the viewport edge during vertical and horizontal document scrolling. Its height follows the menu content, capped to the viewport. Long menus scroll internally while the brand and footer remain visible. Print omits the navigation column.
- **Tablet/mobile navigation:** below 1280 px, the Open navigation button opens a drawer. It uses the existing modal's focus trap, background isolation, Escape handling and focus restoration. Selecting a destination closes it; resizing to desktop removes the drawer and restores document scrolling. The top bar remains sticky.
- **Business switcher:** choose the business button in the top bar. The popup loads actual memberships through the existing authenticated tenant API. Submission uses the existing server-verified activation endpoint; the refreshed identity supplies the new permissions and active tenant. The tenant route subtree remounts so old business records are discarded. A rejected switch leaves the selection and error visible for retry.
- **Find a page:** the search icon opens a finder for authorized navigation destinations. It searches page names locally. It does not search sales, products or customer records. Following a result opens the existing route.
- **User controls:** a breadcrumb, user disclosure, current role, existing light/dark theme toggle and Sign out. No placeholder notification count or disconnected help control was added.
- **Overview:** the existing welcome page remains, with working quick actions filtered by active-business permissions. Separate live dashboards and analytics are not part of D1.

Navigation uses the existing effective permissions, not role names or legacy role IDs. Suppliers now has a direct link to its existing page. Branch setup requires the same product/settings permissions as its route. Server authorization continues to determine access; showing a link never grants permission.

## Reusable dashboard components

These JavaScript components live in `POS Frontend/src/components/dashboard/`. Components for future data sources are built and tested but are not mounted as misleading global filters or invented live metrics.

| Component | Contract and behavior |
| --- | --- |
| `PageHeader` | Title, description, optional actions; updated timestamp only when supplied with a valid timezone |
| `KpiCard` | Supplied value, definition, period, timezone, optional real drill-down; separate loading, error and unavailable states; verified zero remains zero |
| `ChartCard` | Shared chart heading, unit/period metadata, retryable errors, loading and unavailable presentation |
| `TrendChart` | Native SVG line/bar presentation of supplied `{label, value}` series; accessible title/description and an expandable exact-value table; missing points remain gaps |
| `DateRangeFilter` | Validates actual date strings, ordering and the existing 366-day limit; renders only with an apply callback |
| `BranchSwitcher` | Controlled selection from caller-supplied allowed branches; rejects unknown values; renders only with a callback; not wired into the live shell before branch authorization is designed |
| `QuickActionGrid` | Permission-filtered destinations/callbacks; excludes actions without a destination or handler |
| `AlertList` | Supplied exceptions with severity text/icons and optional real destinations; empty/unavailable/error/loading states |
| `EmptyState` | Consistent explanatory state with an optional real destination |

Money display passes original decimal strings directly to `Intl.NumberFormat` and uses the project's explicit currency precision, including two PKR decimals. Approximate numbers position chart marks only; they never calculate financial totals. Comparison percentages require a supplied comparison label. No revenue/profit/cost formula changed.

The implementation reuses semantic tokens, Inter, Remix icons, one theme provider, the global loader, regional loading states, popup forms and toasts. Charts use native SVG, so no chart dependency was installed. The existing local components remain **shadcn-style JavaScript primitives**, not a newly installed Radix/shadcn component library.

## Implementation map

- `src/layouts/TenantLayout.jsx`: shell composition, breakpoint handling, collapsed preference and tenant-keyed outlet.
- `src/components/layout/`: sidebar, top bar, drawer, business switcher, finder and permission-to-navigation metadata.
- `src/features/dashboard/WorkspaceOverview.jsx`: extracted existing welcome page and actual quick links.
- `src/styles/shell.css`, `dashboard.css`: responsive rules and both-theme component presentation.
- `src/test/DashboardShell.test.jsx`, `DashboardComponents.test.jsx`: 16 new interaction/contract tests.
- `scripts/render-dashboard-preview.mjs`: isolated static component previews. Synthetic examples appear only in this script/tests and the private browser fixture; this script is never imported by the production router.

Existing route URLs, APIs, role grants, authentication, financial posting, database and `.env` were preserved. No platform access, branch assignments, multiple-role schema, Stripe charging or migrations were added.

## Verification and previews

Verified on 2026-11-03:

- `npm.cmd test -- --testTimeout=15000`: **172 tests passed across 15 files**. The initial default-timeout run passed 171 tests and timed out after five seconds in one existing product-popup test. The complete rerun passed, including that test; no assertion was removed or test timeout configuration changed in the project.
- `npm.cmd run build`: passed, including local Remix asset generation.
- Chrome fixture verification: passed. Both themes at 360/768/1280/1440 px, 72 px collapsed navigation and persistence, drawer focus/Escape, permission-filtered finder, real membership activation and old-tenant content removal, exact chart data table, desktop pinning and print behavior were checked. Existing popup validation/edit/archive, toast, loader and actual legacy AdminLTE icon checks also passed. Screenshots were visually reviewed. The fixture serves synthetic records and does not write to Laravel or the POS database.

The sanitized [browser verification report](audit/dashboard-d1-browser.json) records the checks. Manual UAT and production/staging acceptance have not been marked passed by fixture tests.

Before-layout baselines: [light](design/d1/before-light.png), [dark](design/d1/before-dark.png). These are retained from the previous verified UI-1 layout, not a fresh production-data capture.

| Final D1 preview | Light | Dark |
| --- | --- | --- |
| Desktop, 1440 px | [View](design/d1/d1-shell-1440-light.png) | [View](design/d1/d1-shell-1440-dark.png) |
| Desktop, 1280 px | [View](design/d1/d1-shell-1280-light.png) | [View](design/d1/d1-shell-1280-dark.png) |
| Tablet, 768 px | [View](design/d1/d1-shell-768-light.png) | [View](design/d1/d1-shell-768-dark.png) |
| Phone, 360 px | [View](design/d1/d1-shell-360-light.png) | [View](design/d1/d1-shell-360-dark.png) |
| Phone drawer | [View](design/d1/d1-drawer-360-light.png) | [View](design/d1/d1-drawer-360-dark.png) |
| Isolated synthetic component preview | [View](design/d1/d1-components-light.png) | [View](design/d1/d1-components-dark.png) |

Other working states: [collapsed navigation](design/d1/d1-collapsed-dark.png), [page finder](design/d1/d1-navigation-finder.png), [business switcher](design/d1/d1-business-switcher.png), [restricted navigation](design/d1/d1-restricted-navigation.png).

The isolated component preview can be regenerated after a production build:

```powershell
Set-Location 'POS Frontend'
npm.cmd run build
node scripts/render-dashboard-preview.mjs
```

The renderer writes private HTML intended for the local browser fixture, not a public application page. It labels all example metrics as synthetic.

## Scope remaining

After D1, the user separately approved D2 and confirmed its backward-compatible explicit branch policy. [DASHBOARD_D2.md](DASHBOARD_D2.md) documents that implementation, including API/data scope and additional-role support. [D3](DASHBOARD_D3.md) connects real owner/admin widgets and drill-downs. The subsequently requested [D4](DASHBOARD_D4.md) adds cashier/manager views and Hold/Recall. D5–D7 remain pending. The original backend Phase 8 staging/release gates remain; no staging server has been provided.
