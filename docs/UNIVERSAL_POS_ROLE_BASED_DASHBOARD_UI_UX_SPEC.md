# Universal Retail POS — Role-Based Dashboard UI/UX Implementation Specification

**Status:** Proposed design specification for coding agent review and implementation  
**Frontend:** React.js, **JavaScript/JSX only** (no TypeScript)  
**Backend:** Laravel REST API  
**Styling:** Tailwind CSS + existing shadcn/ui JavaScript-compatible components  
**Icons:** **Remix Icon** (`@remixicon/react` or the project's existing Remix Icon integration) — **do not add Lucide**  
**Theme:** Modern Enterprise Blue (light by default) + optional Premium Dark  
**Visual reference:** `docs/role-based-dashboard-reference.png` (copy the approved mockup here if available)

> **Design mockup is illustrative, not a verified live application.** Sample metrics, prices, charts, dates, role permissions and module availability are examples only. Verify existing routes, data, role model and database before implementation. Follow existing architecture documents and preserve all business data.

## 1. Objective and boundaries

Create a single coherent dashboard experience that adapts to each authenticated user's platform/tenant/branch scope, assigned permissions and job responsibilities. Share navigation shell, cards, charts, filters, loading/empty/error states and visual tokens; vary the visible widgets, permitted actions and server-side data scope. Do **not** implement nine separate disconnected frontend applications.

The platform administrator operates outside the tenant context. Tenant staff may belong to multiple businesses/branches. A role is a default permission template, **not** a hardcoded numeric role ID; actual authorization is based on Laravel permissions and tenant/branch membership. A user with multiple roles receives the union of allowed permissions subject to explicit restrictions, and can switch context only where authorized.

### Required dashboard profiles

1. **SaaS Super Admin (platform):** tenants, active/trial/past-due subscriptions, MRR if Stripe billing is active, onboarding, platform incidents, support; no implicit access to private tenant financial data.
2. **Business Owner (tenant):** consolidated net sales, transaction count, gross profit when supported by reliable cost data, stock alerts, branch comparison, top products, returns, cash/receivables and approvals.
3. **Business Admin:** operational sales, active shifts, inventory exceptions, pending purchases, user access and approvals where authorized.
4. **Branch Manager:** only assigned branches; branch sales, average order value, returns, team activity, pending approvals and branch stock issues.
5. **Cashier:** **New Sale** as primary action, open shift/register, own shift sales, held sales, recent receipts and allowed returns; no company-wide profits.
6. **Inventory Manager:** on-hand/available inventory, low/out-of-stock SKUs, stock movement, counts, adjustments and transfers awaiting approval.
7. **Purchasing Manager:** open purchase orders, overdue expected receipts, suppliers, incoming inventory and purchase approvals.
8. **Accountant:** reconciled revenue, cash/bank balances, expenses, refunds, receivables/payables and reconciliation exceptions; avoid presenting gross profit as net profit.
9. **Sales Associate:** personal sales where tracked, customer/product lookup, assigned follow-ups and assisted selling; restricted financial visibility.

Optional **Support Agent** profile only if the product actually has a support module. Keep platform support access explicitly authorized and audited; don't fabricate a support dashboard if there is no support workflow.

## 2. Design tokens

| Token | Light | Dark | Usage |
|---|---|---|---|
| `--brand-primary` | `#2563EB` | `#8B5CF6` | active navigation, primary CTA, selected controls |
| `--app-background` | `#F5F7FB` | `#0B1220` | app background |
| `--sidebar-background` | `#0B1F3A` | `#080F1E` | navigation |
| `--surface` | `#FFFFFF` | `#151F32` | cards/dialogs |
| `--surface-muted` | `#F1F5F9` | `#1D2A42` | table header/secondary controls |
| `--text-primary` | `#1E293B` | `#F8FAFC` | body/headings |
| `--text-secondary` | `#64748B` | `#AAB8CF` | supporting copy |
| `--border` | `#E2E8F0` | `#2C3A51` | separators |
| `--success` | `#10B981` | `#34D399` | positive state |
| `--warning` | `#F59E0B` | `#FBBF24` | attention |
| `--danger` | `#EF4444` | `#FB7185` | error/critical |

Use semantic CSS variables, not hardcoded colors throughout components. Respect WCAG AA text contrast (4.5:1 normal text, 3:1 large text), and ensure semantic states are not color-only. Font: Inter with system fallbacks; base 14–16px, 12px only for secondary metadata. Cards: 10–12px radius, 1px border, restrained shadows. Default spacing scale 4/8/12/16/24/32px. Minimum 44×44px targets for primary touch interactions (especially cashier). Provide visible keyboard focus.

**Theme behavior:** light is the initial default; users can choose light/dark (optional system setting); persist preference locally and apply before paint to prevent flashing. Theme selection does not change tenant data. Use existing shadcn theme conventions if installed, and avoid parallel theme providers.

## 3. Global dashboard shell

### Desktop (>=1280px)
- Fixed or sticky collapsible sidebar (expanded ~240px; collapsed ~72px), with logo, active business/branch, authorized navigation and sign-out.
- Top bar: page title/breadcrumb, branch selector where applicable, date-range filter, global search, notifications, help, theme toggle, profile menu.
- Main content: responsive 12-column grid, KPI row, trends, secondary analytics, exception/approval lists, recent transactions and quick actions.
- No horizontal page overflow. Charts have clear axis units and timezones.

### Tablet (768–1279px)
- Collapsible sidebar drawer; 2-column KPI grid; stacked charts; POS checkout cart remains accessible.

### Mobile (<768px)
- Compact header and drawer navigation; single-column KPI/cards; charts scroll or simplify; essential quick actions prioritized. Large financial tables should use responsive row layouts or controlled horizontal scrolling with labels.

### Universal interactions
- Loading skeletons, empty states with next action, retryable errors, stale-data timestamps and refresh.
- Role/branch/date changes refetch all dependent widgets together and reset incompatible filters.
- Alerts navigate to filtered operational pages; widgets must not be dead-end decoration.
- Notifications show actionable categories; avoid showing sensitive details in cross-tenant push notifications.
- Accessible dialogs, tooltips, menus and data tables with keyboard support.

## 4. Dashboard composition by role

| Role | KPI cards (proposed) | Main visual | Secondary panels | Primary action |
|---|---|---|---|---|
| Platform Admin | active tenants, trialing, past due, MRR | tenant growth, subscription status | payment failures, platform alerts | Manage Tenants |
| Owner | net sales, orders, gross profit*, low stock | sales trend + branch comparison | top products, approvals, returns | View Business Report |
| Business Admin | sales, open shifts, pending orders, low stock | sales by branch/day | pending approvals, exceptions | Review Operations |
| Branch Manager | branch sales, orders, AOV, returns | daily branch sales | staff activity, branch alerts | Review Approvals |
| Cashier | shift state, shift sales, orders, held sales | none required; speed first | recent receipts, register status | **New Sale** |
| Inventory | total SKUs, low stock, out of stock, pending counts | stock in/out trend | transfers, discrepancies | Stock Adjustment |
| Purchasing | open POs, overdue receipts, expected deliveries, supplier count | PO status / spend trend | incoming receipts, supplier alerts | Create PO |
| Accountant | revenue, expenses, receivables, reconciliation exceptions | income vs expense | cash/bank, pending settlements | Reconcile Accounts |
| Sales Associate | own sales, own orders, assigned customers | optional personal trend | product lookup, follow-ups | Find Product |

`*` Gross profit requires reliable cost of goods sold and returns accounting. Hide or label as unavailable if not implemented; never synthesize financial figures from the UI.

### Platform vs tenant segregation
- Platform dashboard at e.g. `/platform/dashboard`, guarded by platform permissions.
- Tenant dashboard at e.g. `/app/dashboard`, guarded by tenant membership and effective permissions.
- Do not expose a tenant switcher to users without membership, and never treat `tenant_id` or `branch_id` from a browser query as authorization.

## 5. Detailed widget behavior

### KPI card
- Label, formatted value, unit/currency, date range, optional comparison trend, tooltip defining calculation, click-through if authorized.
- Do not show an increase as inherently good: returns, failed payments and stockouts need inverse semantics.
- Use locale-aware `Intl.NumberFormat` and tenant currency; preserve decimals and avoid floating-point arithmetic for backend money calculations.

### Sales trend
- Toggle permitted date ranges (today / 7 days / 30 days / custom); backend returns date-bucketed series and comparison basis.
- Net sales definition must be explicit: completed sales minus recognized returns/refunds and discounts according to accounting policy; exclude voided/cancelled sales as appropriate.
- Show timezone and whether data includes tax; compare like-for-like periods.

### Branch comparison
- Show only authorized branches, with same reporting period and currency. If multi-currency, avoid summing without a documented FX policy.

### Exceptions and approvals
- Alert cards for low stock, out-of-stock, failed reconciliation, pending stock adjustments, overdue purchase receipts, expiring trials, and failed subscription invoices as applicable.
- Sort critical first, show count and last update, navigate to source record. Actions must revalidate authorization on Laravel.

### Tables
- Recent sales, top products, purchase orders, pending approvals, inventory discrepancies; server-side filtering/sorting/pagination for large datasets.
- Sensitive columns conditionally rendered AND omitted from API responses when unauthorized.

## 6. Remix Icon standard

Use the existing project's Remix Icon setup. For `@remixicon/react`, representative imports are:

```jsx
import {
  RiDashboardLine,
  RiShoppingCartLine,
  RiStore2Line,
  RiBox3Line,
  RiStackLine,
  RiTruckLine,
  RiTeamLine,
  RiBarChart2Line,
  RiSettings3Line,
  RiSunLine,
  RiMoonLine,
  RiNotification3Line,
} from '@remixicon/react';
```

Confirm exported icon names against the installed package version before coding. Use one consistent line-icon family; choose filled variants only for selected/active states. Suggested navigation: dashboard, POS, products, inventory, purchasing, customers, suppliers, accounting, reports, users/roles, settings. **Do not introduce Lucide icon dependencies.**

## 7. Proposed frontend folder structure (adapt to existing repository)

```text
frontend/src/
  app/
    App.jsx
    routes.jsx
  layouts/
    PlatformLayout.jsx
    TenantLayout.jsx
  components/
    layout/{AppSidebar,Topbar,BranchSwitcher,ThemeToggle}.jsx
    dashboard/{KpiCard,ChartCard,DateRangeFilter,AlertList,QuickActionGrid,DataTable}.jsx
    ui/                      # reuse installed shadcn components
  features/dashboard/
    pages/
      PlatformAdminDashboard.jsx
      OwnerDashboard.jsx
      BusinessAdminDashboard.jsx
      BranchManagerDashboard.jsx
      CashierDashboard.jsx
      InventoryDashboard.jsx
      PurchasingDashboard.jsx
      AccountantDashboard.jsx
      SalesAssociateDashboard.jsx
    components/
    config/dashboardProfiles.js
    services/dashboardApi.js
    hooks/useDashboard.js
  hooks/usePermissions.js
  providers/ThemeProvider.jsx
  styles/tokens.css
```

Avoid duplicate widgets/pages where one configurable component works. The backend decides effective permissions and scopes; frontend profile config determines presentation only.

## 8. Laravel API and security contract

Inspect existing APIs before adding routes. Suggested **versioned** endpoints:

```text
GET /api/v1/me/context                # memberships, effective permissions, allowed branches
GET /api/v1/platform/dashboard       # platform-only, aggregate platform KPIs
GET /api/v1/dashboard/summary        # tenant/branch-scoped KPIs
GET /api/v1/dashboard/sales-trend
GET /api/v1/dashboard/branch-performance
GET /api/v1/dashboard/top-products
GET /api/v1/dashboard/alerts
GET /api/v1/dashboard/recent-sales
GET /api/v1/dashboard/approvals
GET /api/v1/dashboard/shift-summary
GET /api/v1/dashboard/inventory-summary
GET /api/v1/dashboard/purchasing-summary
GET /api/v1/dashboard/accounting-summary
```

**Scope rules:** authenticate user; resolve tenant from validated membership/context; check permission for each metric and action; constrain branch IDs to assigned branches; enforce tenant IDs on every DB query, cache key and background job. Use API resources to avoid returning entire Eloquent models or hidden financial fields. Apply rate limits, query limits and indexed date filters. Do not leak platform metrics to tenant users.

Suggested response envelope:

```json
{
  "data": {
    "net_sales": { "amount_minor": 2458000, "currency": "USD", "change_pct": 12.5 },
    "orders": 384,
    "low_stock_skus": 18
  },
  "meta": {
    "scope": "tenant",
    "branch_ids": [1],
    "timezone": "Asia/Karachi",
    "from": "2026-11-01",
    "to": "2026-11-02",
    "generated_at": "2026-11-02T12:00:00Z"
  }
}
```

This is a **contract example**, not an assertion that these fields currently exist. Define actual financial reporting semantics in the backend before connecting widgets. Avoid returning metrics the requester is not authorized to view.

## 9. Permissions and role mapping

Define permissions as capabilities, such as `dashboard.platform.view`, `dashboard.business.view`, `sales.view`, `sales.create`, `inventory.view`, `inventory.adjust`, `purchasing.view`, `purchasing.create`, `finance.view`, `reports.view`, `approvals.manage`, `billing.manage`, `users.manage`.

Seed recommended role templates per tenant, allow authorized admins to customize. A cashier may have `sales.create` and `shift.view_own` without `finance.view`; inventory managers may view stock without seeing cost valuation unless explicitly granted. Ensure branch-level restrictions apply even when a broad role permission exists. Server must return 403 on unauthorized endpoints, not just hide menus.

## 10. Example role-aware rendering (JavaScript/JSX)

```jsx
const profiles = {
  owner: ['netSales', 'orders', 'grossProfit', 'lowStock', 'salesTrend'],
  cashier: ['shiftStatus', 'shiftSales', 'heldSales', 'recentReceipts'],
  inventory_manager: ['stockSkus', 'lowStock', 'stockMovements', 'pendingCounts'],
};

export function DashboardSections({ profile, allowedWidgets, widgets }) {
  const requested = profiles[profile] ?? [];
  return requested
    .filter((key) => allowedWidgets.includes(key))
    .map((key) => {
      const Widget = widgets[key];
      return Widget ? <Widget key={key} /> : null;
    });
}
```

This is presentation logic only. Laravel must enforce the corresponding permissions and query scopes. Prefer backend-supplied capabilities over trusting a user-editable role string.

## 11. Step-by-step coding agent implementation plan

### Phase D0 — Read-only audit and approval
1. Read `AGENT.md` / `AGENT(1).md`, architecture blueprint and existing UI theme guide; inspect React routing, installed Tailwind/shadcn/Remix packages, auth, tenant/branch model, role/permission tables, dashboards and APIs.
2. Produce a gap analysis of current vs proposed roles/widgets/endpoints; identify unavailable data, unsupported modules and finance definitions.
3. Do **not** edit live data, run destructive migrations or replace functioning screens during audit.

**Acceptance:** documented existing state, dependency gaps and approved widget/data map.

### Phase D1 — Design foundation
1. Add semantic light/dark tokens and global typography; reuse existing theme provider where possible.
2. Implement reusable shell: sidebar, topbar, tenant/branch switcher, date filter, theme toggle and mobile drawer.
3. Add Remix Icon wrappers if useful; verify icon exports.
4. Implement shared KPI, chart, table, alert, quick action, skeleton, empty/error components.

**Acceptance:** light default, dark mode works, responsive layout, no unauthorized menu items, keyboard access.

### Phase D2 — Permission and dashboard API foundation
1. Resolve authenticated platform/tenant/branch context and permissions server-side.
2. Build authorized dashboard summary endpoints, API resources and cached queries with scoped cache keys.
3. Define tested financial metrics, return/cancel rules and date/timezone boundaries.
4. Add API integration tests for cross-tenant and cross-branch access.

**Acceptance:** backend enforces data isolation, no client-only authorization.

### Phase D3 — Owner and Business Admin
1. Implement owner dashboard from reference: KPIs, sales trend, branch split, top products, alerts and quick actions.
2. Implement admin operational variant, preserving existing modules and routing.
3. Verify chart calculations against backend reporting fixtures.

**Acceptance:** meaningful drill-downs and accurate scoped figures.

### Phase D4 — Cashier and Branch Manager
1. Implement cashier shift-first landing page with prominent New Sale, Hold/Recall and recent receipts.
2. Implement branch manager sales, team/shift summary, approvals and branch alerts.
3. Test restricted financial visibility and cross-branch permissions.

**Acceptance:** cashier can start checkout quickly; managers cannot access unauthorized branches.

### Phase D5 — Inventory, Purchasing and Accounting
1. Inventory: stock KPIs, movement trend, low-stock list, adjustments/transfers.
2. Purchasing: PO status, expected receipts, overdue orders, supplier overview.
3. Accountant: revenue/expense trend, receivables, cash/bank, reconciliation alerts.
4. Do not invent missing accounting or procurement data; show a clearly labeled unavailable state or defer the widget.

**Acceptance:** each dashboard's numbers reconcile to its underlying records.

### Phase D6 — Platform Admin and Sales Associate
1. Separate platform shell/dashboard for tenant/subscription aggregates and incidents.
2. Add sales associate workspace if permissions and data exist.
3. Protect sensitive tenant details from platform support users by default.

**Acceptance:** strict platform/tenant segregation.

### Phase D7 — QA, rollout and documentation
1. Test both themes, 360/768/1280/1440 widths, keyboard, screen reader labels, empty states, slow network and API failures.
2. Test all role/branch/tenant combinations and multi-role users.
3. Compare dashboard totals with existing financial reports and known fixtures.
4. Roll out behind a feature flag, preserve old dashboard during pilot, collect cashier/manager feedback, then gradually switch defaults.
5. Update developer docs and screenshot references.

**Acceptance:** signed-off role matrix, no unauthorized data leakage, regression tests pass.

## 12. Non-negotiable UX requirements

- All dashboards must use **one shared design system**, not different colors/spacing for each role.
- Theme A is default; Theme B is an optional user preference.
- Use **Remix Icons**, not Lucide.
- React code must use **JavaScript/JSX**, not TypeScript/TSX.
- Keep the cashier screen optimized for speed and touch input; avoid analytics overload.
- All money/date labels must specify currency, timezone and reporting period.
- No hardcoded fake KPI values in production; mock data only in isolated previews/tests.
- Preserve existing sales, stock, customers, permissions, billing and accounting behavior.
- Never create migrations just for visual design changes unless a genuinely missing backend feature is approved.
- Use incremental PRs/commits per phase and show before/after screenshots for review.

## 13. Ready-to-copy Codex / Antigravity prompt

> Read `docs/UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md` and the existing project `AGENT.md` and architecture/theme documentation. Implement professional role-based dashboards using **React JavaScript/JSX**, **Tailwind CSS**, existing **shadcn/ui** components, and **Remix Icons** (do not install or use Lucide). Use **Modern Enterprise Blue light theme as default** and **Premium Dark as optional theme**, sharing one responsive shell and design system across SaaS Super Admin, Business Owner, Business Admin, Branch Manager, Cashier, Inventory Manager, Purchasing Manager, Accountant and Sales Associate. Begin with **Phase D0 read-only audit**; report existing components, available APIs, role/permission model and missing data before changing code. Then implement each phase sequentially, seeking approval before expanding scope. All metrics and permissions must be enforced in Laravel and scoped by authenticated platform/tenant/branch context. Preserve existing data and working features, do not fabricate KPI data, and add role/tenant isolation and UI regression tests. Use the approved reference image for visual direction, not as proof of existing features.

## 14. Review and sign-off checklist

- [ ] Role templates and permitted widgets reviewed by product owner
- [ ] Platform vs tenant vs branch access rules approved
- [ ] Light and dark theme screenshots approved
- [ ] Remix Icon names and visual consistency checked
- [ ] Desktop, tablet and mobile layouts reviewed
- [ ] Owner and Cashier dashboards tested with realistic scenarios
- [ ] Inventory/Purchasing/Accounting data availability verified
- [ ] Dashboard totals reconcile with backend definitions
- [ ] All routes, widgets and actions enforce Laravel permissions
- [ ] Cross-tenant and cross-branch security tests pass
- [ ] Existing POS checkout and historical data unaffected
- [ ] Release staged behind feature flag with rollback path
