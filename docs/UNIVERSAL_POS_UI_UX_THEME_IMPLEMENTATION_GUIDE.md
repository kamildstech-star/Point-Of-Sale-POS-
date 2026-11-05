# Universal Retail POS — UI/UX Design System & Implementation Guide

**Decision:** Theme A, **Modern Enterprise Blue (Light)**, is the default. Theme B, **Premium Dark**, is an optional user-selectable mode. Both use the **same layout, components, navigation, and interaction patterns**.

**Project:** Universal Retail POS — multi-tenant SaaS, React.js frontend (**JavaScript / JSX only; NO TypeScript**) and Laravel REST API backend.

**Icons:** **Remix Icon** (not Lucide). **Styling:** Tailwind CSS + shadcn/ui-style components adapted to JavaScript and Remix Icon. **Font:** Inter with system-font fallback.

> This is a proposed design specification based on the two approved-for-comparison mockups and the existing POS architecture documentation. The mockups are illustrative, not screenshots of implemented software. Existing project code and installed dependencies must be inspected before making changes.

---

## 1. Design goals and non-negotiable decisions

1. One coherent visual language for the tenant dashboard, checkout, inventory, purchasing, reporting, customers, and settings.
2. Default to light theme; allow switching to dark theme in the user menu/settings. Respect system preference only if the user has not explicitly selected a theme; the product's initial default remains light.
3. **Remix Icon exclusively** for the application icon system. Do not install/use `lucide-react` for new screens; replace any shadcn/ui-generated Lucide icon imports with Remix Icon equivalents.
4. React **`.jsx` / `.js` only**, no `.tsx`, `.ts`, TypeScript types, or TypeScript configuration.
5. Reuse existing Laravel API endpoints and business rules; UI migration must not alter tenant isolation, stock calculations, permissions, or financial records.
6. Admin screens prioritize scanability and efficient tables; checkout prioritizes speed, keyboard/barcode input, touch targets, and persistent cart visibility.
7. Theme changes must be presentation-only and must never alter permissions, data, pricing, or transactions.

## 2. Theme identity

| Element | Theme A: Light (default) | Theme B: Dark (optional) |
|---|---|---|
| App background | `#F5F7FB` | `#0B1220` |
| Surface/card | `#FFFFFF` | `#151F31` |
| Elevated surface | `#FFFFFF` | `#1B2940` |
| Sidebar | `#0B1F3A` | `#0A1020` |
| Sidebar foreground | `#F8FAFC` | `#F8FAFC` |
| Main text | `#1E293B` | `#F1F5F9` |
| Muted text | `#64748B` | `#A7B4C8` |
| Border | `#E2E8F0` | `#2A3950` |
| Primary/action | `#2563EB` | `#8B5CF6` |
| Primary hover | `#1D4ED8` | `#7C3AED` |
| Success | `#059669` | `#34D399` |
| Warning | `#D97706` | `#FBBF24` |
| Destructive | `#DC2626` | `#F87171` |
| Focus ring | `#60A5FA` | `#A78BFA` |

**Color semantics:** Use design tokens (`bg-background`, `text-foreground`, `bg-card`, `border-border`, `bg-primary`) rather than hardcoded color literals in individual components. Do not use color alone to communicate transaction status. Check WCAG contrast in actual UI; token values above are starting points, not proof of contrast compliance.

**Typography:** Inter 14px base for administration; 16px base for POS touch controls where practical. Page title 24–28px/semibold; section heading 16–18px/semibold; table text 13–14px; helper text >=12px with adequate contrast. Use tabular numbers for amounts and quantities.

**Spacing & shape:** 4px spacing scale, 8/12/16/24px common gaps; cards 12px radius; controls 8–10px radius; subtle borders and minimal shadows; 1px dividers; avoid excessive glassmorphism/gradients. For checkout, minimum ~44×44px touch targets where practical.

## 3. Navigation and layout

### 3.1 Tenant application shell

- **Desktop sidebar:** 232–248px expanded, 64–72px collapsed, persistent on wide screens; scrolls independently when needed.
- **Top bar:** 64px, with breadcrumb/title, global search, notifications, branch selector, user menu, and theme toggle.
- **Content:** responsive max-width as appropriate; 20–24px padding on desktop, 12–16px on narrow screens.
- **Navigation:** Dashboard; POS / Sales; Products & Variants; Inventory; Purchasing; Customers; Suppliers; Returns; Accounting / Expenses; Reports; Settings; Billing. Render only authorized entries.
- **Multi-tenant:** business selector only for memberships the user has; branch selector only for authorized branches. Never treat the frontend selector as the security boundary.
- **Platform SaaS admin:** separate shell/navigation from tenant business app; no accidental cross-tenant UI context.

### 3.2 Responsive behavior

- >=1280px: sidebar + multi-column dashboard; POS product area and cart side by side.
- 768–1279px: collapsible sidebar; dashboard cards reflow; POS cart stays visible or uses an accessible fixed panel.
- <768px: drawer navigation; dashboards and forms stack; POS supports a clear cart toggle and sticky checkout total; complex data tables can scroll horizontally with column controls.
- Do not force the desktop checkout grid onto narrow screens; test common cashier tablet widths.

## 4. Core screens and patterns

### 4.1 Dashboard

**Header:** Dashboard title, date range, branch filter, and quick actions.

**KPI row:** Today's sales, order count, low-stock SKUs, customers (optional gross profit only when reliably computed). Each card includes a clear label, formatted value, comparison period, and trend indication.

**Main area:** Sales trend chart; sales by category; recent sales table; quick actions (New Sale, Add Product, Purchase, Reports); operational alerts (low stock, pending returns, failed syncs).

**Data integrity:** Clearly label net/gross sales, discounts, refunds, taxes, and reporting period. Never fabricate metrics. Handle loading, empty, error, and permission-denied states.

### 4.2 POS checkout (highest-priority workflow)

**Left/product area:** Persistent autofocusable search for name, SKU, or barcode; optional category tabs; compact product grid/list; stock availability; product variant selection; keyboard shortcuts; scanner input that does not lose focus.

**Right/cart area:** Item name and variant, quantity controls, unit price, discounts (permission-gated), line totals, remove action, subtotal, tax, discount, amount due, payment method(s), and clear checkout CTA.

**Controls:** Cash, card, and other methods are **tenant retail tender types** and must reflect actual backend capabilities; do not imply Stripe SaaS subscription checkout is the in-store card processor. Hold/recall and split tender only when supported by backend workflows.

**Safety:** Server recomputes totals, checks inventory with transactions/locking, and ensures idempotency. Disable repeated submit while pending; clearly distinguish pending, completed, and failed payment/sale outcomes. Invoice/receipt shown after backend confirms success.

**Accessibility:** Full keyboard path, visible focus, descriptive errors, accessible modal focus handling, adequate target sizes, and no hover-only critical actions.

### 4.3 Products & inventory

- Product list: search, category/brand filters, SKU/barcode, variant count, selling price, stock status, and actions.
- Product editor: sections for core info, variants, pricing, taxes, images, and inventory rules; clear required-field errors.
- Inventory: current stock by branch/location, movements, adjustments with reason, reorder alerts, and stock transfer status when supported.
- Dense tables with saved filters, column visibility, sort, pagination, row actions, export (permission-gated), and consistent empty states.

### 4.4 Purchasing, sales, returns, customers and reports

- Purchasing: purchase orders, supplier, receiving state, item quantities/costs, approval and receiving history.
- Sales: transaction list with status, payment state, returns/refunds, date filters, and receipt actions.
- Returns: original transaction reference, eligible quantities, reason, refund disposition, and audit trail; never present a simple stock increment as a completed refund.
- Customers/suppliers: clean searchable directory and detail views; mask sensitive fields where appropriate.
- Reports: date/branch filters, transparent definitions, charts and tables, export and print styles, explicit timezone and currency.

### 4.5 SaaS billing

Billing pages use the same theme but are distinct from retail checkout: plans, subscription status, invoice history, renewal date, and Stripe Customer Portal entry. Never activate subscriptions from the redirect page alone; Laravel must confirm verified Stripe events.

## 5. Component inventory

Build/reuse: `AppShell`, `Sidebar`, `Topbar`, `Breadcrumbs`, `ThemeToggle`, `TenantSwitcher`, `BranchSwitcher`, `PageHeader`, `StatCard`, `ChartCard`, `DataTable`, `FilterBar`, `StatusBadge`, `EmptyState`, `ErrorState`, `LoadingSkeleton`, `ConfirmDialog`, `FormField`, `MoneyText`, `QuantityInput`, `ProductCard`, `CartItem`, `CartSummary`, `PaymentMethodPicker`, `Toast`, `PermissionGate`.

**shadcn/ui caveat:** shadcn/ui templates frequently use TypeScript and Lucide by default. Use JavaScript-compatible components (or convert generated files to JSX) and replace their icon imports with Remix Icon. Follow the actual installed Tailwind/shadcn version and avoid copying incompatible configuration snippets blindly.

## 6. Remix Icon specification

**Recommended package:** `@remixicon/react` with named imports (verify actual package exports in installed version). Example mappings:

| Meaning | Remix Icon component |
|---|---|
| Dashboard | `RiDashboardLine` |
| POS / cart | `RiShoppingCart2Line` |
| Products | `RiBox3Line` |
| Inventory | `RiArchiveStackLine` |
| Purchasing | `RiTruckLine` |
| Customers | `RiGroupLine` |
| Suppliers | `RiBuildingLine` |
| Reports | `RiBarChartBoxLine` |
| Accounting | `RiWallet3Line` |
| Settings | `RiSettings3Line` |
| Search | `RiSearchLine` |
| Notifications | `RiNotification3Line` |
| Theme light | `RiSunLine` |
| Theme dark | `RiMoonLine` |
| Add | `RiAddLine` |
| Delete | `RiDeleteBinLine` |
| More actions | `RiMore2Line` |

Use one icon family, generally outline (`Line`) for navigation and controls. Standard sizes: 18–20px navigation, 16px inline actions, 20–24px quick-action icons. Every icon-only button needs an accessible label.

```jsx
// Example: JavaScript/JSX only
import { RiDashboardLine, RiShoppingCart2Line, RiMoonLine } from '@remixicon/react';

export const menuItems = [
  { label: 'Dashboard', path: '/app/dashboard', icon: RiDashboardLine },
  { label: 'POS', path: '/app/pos', icon: RiShoppingCart2Line },
];

export function DarkModeIcon() {
  return <RiMoonLine size={20} aria-hidden="true" />;
}
```

If the repository already uses Remix Icon via its CSS icon font (`ri-*` classes), the coding agent may retain that approach for consistency instead of installing a second icon delivery method. Do **not** mix two icon systems without a documented reason.

## 7. Theme tokens and implementation example

The example below uses semantic CSS custom properties, with a `.dark` class on the document root. Map these variables into the **installed** Tailwind/shadcn setup (Tailwind v3 and v4 have different configuration conventions). This is a reference, not a claim that it can be pasted unchanged into every setup.

```css
:root {
  color-scheme: light;
  --app-bg: #F5F7FB;
  --app-card: #FFFFFF;
  --app-elevated: #FFFFFF;
  --app-sidebar: #0B1F3A;
  --app-text: #1E293B;
  --app-muted: #64748B;
  --app-border: #E2E8F0;
  --app-primary: #2563EB;
  --app-primary-hover: #1D4ED8;
  --app-success: #059669;
  --app-warning: #D97706;
  --app-danger: #DC2626;
  --app-focus: #60A5FA;
}

.dark {
  color-scheme: dark;
  --app-bg: #0B1220;
  --app-card: #151F31;
  --app-elevated: #1B2940;
  --app-sidebar: #0A1020;
  --app-text: #F1F5F9;
  --app-muted: #A7B4C8;
  --app-border: #2A3950;
  --app-primary: #8B5CF6;
  --app-primary-hover: #7C3AED;
  --app-success: #34D399;
  --app-warning: #FBBF24;
  --app-danger: #F87171;
  --app-focus: #A78BFA;
}

body { background: var(--app-bg); color: var(--app-text); }
```

### 7.1 JavaScript theme provider

Persist the user's explicit choice (`light`, `dark`) in local storage, and apply the root class before/at app initialization to reduce flash. For SSR/hydration, follow the application's actual framework constraints. Example for a client-rendered React app:

```jsx
import { createContext, useContext, useEffect, useState } from 'react';

const ThemeContext = createContext(null);

function getInitialTheme() {
  try {
    return localStorage.getItem('pos-theme') === 'dark' ? 'dark' : 'light';
  } catch {
    return 'light';
  }
}

export function ThemeProvider({ children }) {
  const [theme, setTheme] = useState(getInitialTheme);

  useEffect(() => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    try { localStorage.setItem('pos-theme', theme); } catch { /* unavailable */ }
  }, [theme]);

  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) throw new Error('useTheme must be used inside ThemeProvider');
  return context;
}
```

```jsx
import { RiMoonLine, RiSunLine } from '@remixicon/react';
import { useTheme } from './ThemeProvider';

export function ThemeToggle() {
  const { theme, setTheme } = useTheme();
  const isDark = theme === 'dark';
  return (
    <button
      type="button"
      aria-label={isDark ? 'Switch to light theme' : 'Switch to dark theme'}
      aria-pressed={isDark}
      onClick={() => setTheme(isDark ? 'light' : 'dark')}
    >
      {isDark ? <RiSunLine size={20} /> : <RiMoonLine size={20} />}
    </button>
  );
}
```

**Implementation note:** A production system may save the theme preference to the user's profile via Laravel for cross-device synchronization; local storage alone is sufficient for a first version. If adding a `system` option later, honor `prefers-color-scheme` only for that explicit mode. Default remains Theme A.

## 8. Data visualization and financial formatting

- Use a chart library already installed or choose one consistent React chart library; chart colors derive from theme tokens.
- Re-render or update chart axes, gridlines, tooltips and legends on theme changes.
- Amounts must use tenant currency and locale via `Intl.NumberFormat`; do not hardcode `$` or USD in production.
- Use locale/timezone-aware dates; distinguish transaction date from posting date.
- Show zero, empty, pending, and error states explicitly; avoid misleading trend percentages without a valid comparison baseline.

## 9. Accessibility, performance and quality gates

- Target WCAG 2.2 AA where feasible: keyboard navigation, visible focus, contrast verification, accessible forms, focus-managed dialogs, screen-reader labels, and reduced-motion support.
- Support 200% zoom and narrow tablet widths without clipped checkout controls.
- Tables must remain usable with thousands of records using server-side pagination/filtering; virtualize only when justified.
- Debounce search where appropriate, but barcode scanning must remain responsive.
- Avoid large chart/image bundles on the cashier route; lazy-load reporting modules.
- Show toast feedback without relying on it as the only indicator of a saved sale.
- Confirm destructive operations and clearly differentiate void, cancel, return and refund.
- Provide skeletons, offline/network-error messaging, and retry strategies that do not duplicate financial transactions.

## 10. Step-by-step implementation plan for Codex

### Phase UI-0 — Inspect and inventory (no changes)

1. Read the current architecture guide and existing frontend/backend code.
2. Confirm frontend is React JS/JSX, Tailwind version, shadcn/ui setup, router, icon delivery method, auth flow, existing UI components and available APIs.
3. List all current pages and identify which are legacy Blade versus React.
4. Produce a migration matrix of existing page → target component/layout; note missing backend APIs.
5. Preserve uncommitted changes and existing working functionality.

**Exit:** Written audit and scoped change plan approved; no destructive migrations.

### Phase UI-1 — Design tokens and foundations

1. Add semantic light/dark tokens and typography/spacing rules.
2. Implement ThemeProvider and ThemeToggle, light by default, dark persisted per user/browser.
3. Standardize Remix Icon usage and remove newly introduced Lucide imports.
4. Add base buttons, inputs, selects, dialogs, badges, cards, tables, alerts, skeletons, and toasts.
5. Test both themes and accessibility of core controls.

**Exit:** Components render consistently in both themes without changing application logic.

### Phase UI-2 — Application shell

1. Build responsive sidebar, topbar, breadcrumb, global search entry, notification entry, branch/business selectors, user menu, and theme toggle.
2. Connect navigation to actual routes and authorization.
3. Create separate platform-admin and tenant-admin shells where required.
4. Test mobile/tablet/desktop and collapsed navigation.

**Exit:** Navigation is usable, tenant context is clear, and unauthorized links are hidden (with Laravel still enforcing authorization).

### Phase UI-3 — Dashboard

1. Implement KPI cards, trend chart, category breakdown, recent sales, quick actions, low-stock panel.
2. Connect to real Laravel APIs, with date/branch filters.
3. Add loading, error, empty, and no-permission states.
4. Verify metric definitions with backend accounting logic.

**Exit:** Dashboard uses real data and renders correctly in light/dark modes.

### Phase UI-4 — POS checkout

1. Build product search/barcode field, category filter, product grid, variant picker, cart, totals, payment actions, hold/recall if supported.
2. Wire to existing sale API with server-side pricing/stock checks.
3. Implement loading, validation, network recovery, success/receipt, and duplicate-submit prevention.
4. Test cashier keyboard/scanner workflow, tablet layouts, and accessible dialogs.

**Exit:** End-to-end sale flow is accurate and safe; both themes supported.

### Phase UI-5 — Operational modules

1. Products/variants, inventory, purchasing, sales/returns, customers, suppliers, expenses, reports and settings.
2. Standardize data tables, filters, dialogs, forms, empty states and action patterns.
3. Connect to real APIs and role/permission controls.
4. Avoid placeholder buttons or simulated business actions in production.

**Exit:** All scoped modules follow the design system and retain existing business behavior.

### Phase UI-6 — Billing and SaaS administration

1. Apply design system to plans, subscription, invoices, billing settings and tenant management.
2. Keep Stripe subscription checkout separate from POS tender UI.
3. Show webhook-confirmed billing states and safe error/retry messages.

**Exit:** Consistent SaaS UI with correct tenant separation.

### Phase UI-7 — Polish and release

1. Run responsive, keyboard, accessibility and contrast reviews.
2. Test both themes across all routes and modal/dropdown states.
3. Verify no TypeScript or new Lucide dependency was introduced.
4. Check performance and error handling, add screenshots and user acceptance tests.
5. Roll out incrementally behind feature flags if replacing an active production UI.

**Exit:** Signed-off design QA, regression tests passing, rollback plan available.

## 11. Acceptance checklist

- [ ] Theme A is default for new users.
- [ ] Theme B is accessible from user menu/settings.
- [ ] Theme choice persists across refresh; optional backend profile sync documented.
- [ ] No TypeScript files or types added.
- [ ] Remix Icon is used consistently; no new Lucide imports.
- [ ] Light and dark screens share identical information architecture.
- [ ] Dashboard, POS, inventory, reports, billing, and dialogs are visually consistent.
- [ ] Tenant and branch context are visible and authorization remains server-enforced.
- [ ] POS sale and refund workflows remain correct; no UI-only calculations become authoritative.
- [ ] Keyboard, barcode, tablet, zoom, focus and contrast tests pass.
- [ ] Currency, date/time, totals and reports use real tenant settings and verified API data.
- [ ] No destructive migrations or loss of legacy business data.

## 12. Copy/paste master prompt for Codex

> You are updating my Universal Retail POS multi-tenant SaaS. Read `AGENT.md` (or the actual current architecture guide) and `docs/UNIVERSAL_POS_UI_UX_THEME_IMPLEMENTATION_GUIDE.md` first. Implement **Theme A: Modern Enterprise Blue (light) as the default**, with **Theme B: Premium Dark as an optional toggle**. The frontend must be **React JavaScript/JSX only, never TypeScript**; the backend is **Laravel REST API**. I use **Remix Icon**, NOT Lucide. Use existing Remix Icon integration where possible; otherwise use `@remixicon/react` and replace shadcn/ui Lucide defaults. Follow the guide's tokens, layout, components, POS interaction patterns, accessibility requirements, and phases. Begin with UI-0 inspection, report the existing architecture and the exact files you plan to change, then implement UI-1 only. Do not rewrite the whole project at once. Preserve all working features, tenant security, stock/payment calculations, migrations, and existing data. Use real APIs, no fake production metrics or inert controls. Add tests and show screenshots or a reviewable preview for both themes before proceeding to the next phase.

---

## 13. Reference mockups

Two reference images were generated in the conversation:

- **Light / Enterprise Blue** — dashboard and POS checkout.
- **Dark / Premium Dark** — matching dashboard and POS checkout.

The comparison image is a visual reference, not a functional design artifact. Keep it alongside this guide in `docs/design/` if desired. The implementation must follow the specifications above and be checked against real application behavior.

## 14. Existing-project cautions

The uploaded project guide describes a Laravel Blade POS with school/product/size stock, incomplete storefront APIs, and financial/inventory consistency risks. Before converting the UI, verify the current working tree and the actual database; the guide is a source-code audit rather than a live-schema verification. Do not let a new dashboard mask discrepancies in returns, cancellations, stock balances, or sales reports. Implement UI modernization separately from backend correctness fixes, with explicit test coverage for both.
