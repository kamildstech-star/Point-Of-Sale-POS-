# Compact Enterprise UI

For the complete reusable theme specification, component rules and UI writing guidance, see [Compact UI/UX Theme & Design Language](UNIVERSAL_POS_COMPACT_UI_UX_DESIGN_LANGUAGE.md).

Implemented 2026-11-04 from `Universal_POS_Compact_Professional_UI_UX_Guidelines.md` supplied by the user. This is a sizing refinement of the existing React workspace, separate from dashboard phases D0–D7. D5–D7 remain pending.

## Audit findings

The application already uses local Inter, Remix icons, Tailwind utilities, native JavaScript shadcn-style primitives, a shared light/dark theme, validated forms and focus-managed portal dialogs. These were reused without new dependencies.

The main causes of excess size were overlapping native and shared-component styles: 72px topbars, 240px sidebars with 44px navigation rows, 24px cards, 16px vertical table-cell padding, 20px label margins, and repeated KPI definitions and periods. Listing action buttons made ordinary product rows 73px tall. Checkout already had a larger sizing rule, but it covered only part of the screen and did not reach portal forms.

Representative screens inspected: Owner Overview, Products, Add product dialog, Reports and Point of sale. Baseline screenshots and measurements were captured from the previous production build before changing it.

## Shared implementation

- `POS Frontend/src/styles/tokens.css`: independent typography, control, table, card, spacing, navigation and radius tokens. The root uses the browser's normal font size; application body text is 0.875rem (14px with the normal 16px browser default). The color palette stays in the existing light/dark theme.
- `src/styles/app.css`, `foundations.css`, `shell.css`, `modal.css`, `icons.css`, `dashboard.css`, `tailwind.css`: existing rules now consume the shared sizing system instead of adding another override stylesheet. Normal surfaces use 8px radii and 16px padding. Existing print styles remain in place.
- `src/components/ui/DensityProvider.jsx`, `src/layouts/TenantLayout.jsx`, `src/components/Modal.jsx`: management routes use `compact`; `/pos` uses `comfortable`. React context carries density into body portals. This changes presentation without resetting form values. Small screens and coarse pointers receive larger controls in either profile.
- `src/components/ui/primitives.jsx`: shared button sizes `sm`, `default`, `icon`, `touch`; input/select touch variants; explicit table density when needed. Existing native form semantics and validation remain authoritative.
- `src/components/ui/TableViewport.jsx`: shared table overflow region with a measured scroll hint and keyboard access when horizontal scrolling is needed. Columns remain available on small screens.
- `src/components/ui/tableColumns.js`: explicit numeric column metadata for remote lists and setup references. Money and quantity columns align right with tabular figures; identifiers, telephone numbers and SKUs keep their original string values.
- Listing/detail wrappers updated in Catalog, Product, References, Team, Inventory, Purchasing, Sales, Cash, Returns, Held Carts, Legacy Evidence, POS Settings and Workspace Tools; dashboard tables and chart data use the same viewport.
- `src/components/dashboard/KpiCard.jsx`: values and primary links stay visible; optional metric definitions and reporting periods are available through a keyboard-accessible “About this metric” disclosure. The dashboard's overall scope/date information stays visible.
- `src/components/layout/AppSidebar.jsx`: 18px navigation icon artwork; readable 13px labels; subdued active background with an accent indicator. Its content-sized, fixed position and collapse/drawer behavior are preserved.

The current directly visible Edit/Delete actions are retained because those are core actions explicitly requested earlier. No new sorting, filtering, bulk actions or APIs were invented. Existing search, pagination, CSV export, permissions, validation, toasts and loaders continue to use their existing implementations.

## Sizing targets

| Element | Previous UI | Updated compact management UI |
| --- | --- | --- |
| Body | 14px | 14px at the normal browser font setting |
| Product page title | 28px | 22px |
| Owner page title at 1440px | 31.68px | 22px |
| Sidebar width | 240px | 232px; collapsed width remains 72px |
| Navigation label / icon | 12px / 20px | 13px / 18px |
| Navigation minimum height | 44px | 40px desktop; larger on touch/mobile |
| Topbar minimum height | 72px | 58px; grows when controls wrap |
| Standard control minimum height | 40px; some buttons taller | 36px desktop; 48px on touch/mobile |
| Secondary compact toolbar variant | No distinct height | 32px desktop; 48px on touch/mobile |
| Table text / header | 13px / 12px | 13px / 12px |
| Product row | 73px in the measured fixture | 44px target; grows for wrapped content |
| Product rows fully visible at 1440 × 1000 | 8 | 16 with the same synthetic 20-record response |
| Owner KPI height at 1440px | 286.25px | 183.59px with definitions collapsed |
| Standard card padding | 24px | 16px; comfortable cards 20px |
| Label spacing | 20px above, 8px below | 12px above, 4px below |
| Form column gap | 24px | 16px |
| Card radius | 12px | 8px |
| Checkout controls | Mostly 44px inside the cart | At least 48px across the selling desk and its dialogs; Complete sale 52px |

Controls use minimum heights rather than fixed text clipping. A multiline row, translated label or expanded KPI can increase its height. Touch checkout retains 16px entry text and clear payment totals. The cashier dashboard's prominent New Sale action stays 64px.

## Review artifacts

Open the [before/after gallery](design/compact/index.html) or inspect individual screenshots:

| Screen | Before | After (light) | After (dark) |
| --- | --- | --- | --- |
| Owner Overview | [Before](design/compact/before/dashboard-light-1440.png) | [After](design/compact/after/dashboard-light-1440.png) | [Dark](design/compact/after/dashboard-dark-1440.png) |
| Products | [Before](design/compact/before/products-light-1440.png) | [After](design/compact/after/products-light-1440.png) | [Dark](design/compact/after/products-dark-1440.png) |
| Add product | [Before](design/compact/before/product-form-light-1440.png) | [After](design/compact/after/product-form-light-1440.png) | [Dark](design/compact/after/product-form-dark-1440.png) |
| Reports | [Before](design/compact/before/reports-light-1440.png) | [After](design/compact/after/reports-light-1440.png) | [Dark](design/compact/after/reports-dark-1440.png) |
| Checkout | [Before](design/compact/before/checkout-light-1440.png) | [After](design/compact/after/checkout-light-1440.png) | [Dark](design/compact/after/checkout-dark-1440.png) |

All previews use synthetic HTTP fixtures. No customer data, passwords or working-database writes are involved. Baseline and updated measurements are in `docs/audit/compact-ui-before.json` and `compact-ui-after.json`.

## Verification

- **211 React tests passed across 20 files**, including existing role/tenant, CRUD, validation, hold/recall, payment, theme, notification and loading tests. New interaction checks cover portal density without lost input/focus, accessible metric disclosures, and overflow hints that follow measured table width. The complete local report is `docs/audit/private/compact-ui-vitest.json`.
- **97 headless Chrome checks passed**, with **40 updated screenshots** and five baseline screenshots. Public measurements and named checks: [before](audit/compact-ui-before.json), [after](audit/compact-ui-after.json). These include a check that the checkout grand total retains its 20px visual hierarchy.
- **Vite production build passed**. No additional package dependencies were installed. Main JavaScript is approximately 498.58kB before gzip.
- Measured compact desktop values: 22px page title, 36px controls, 44px ordinary product rows, 232px sidebar, 18px navigation icons, 58px topbar. The same catalog fixture shows 16 complete rows instead of 8.

The browser harness covers desktop widths 1280/1440/1920, tablet 768, mobile 360, both themes, contrast, visible keyboard focus, fixed sidebar, long product content, top-of-form validation, checkout/held-cart dialog density, empty/error/retry states and barcode keyboard-wedge input.

Zoom coverage uses the equivalent CSS viewport and device pixel ratio at 100%, 125% and 200% in headless Chrome. It verifies reflow at those effective sizes; it is not a claim that a physical browser's zoom UI or a physical scanner/touchscreen was operated. A hardware checkout pass remains useful before deployment. Existing subscription/staging release gates from the implementation plan are unchanged.

Reproduce from `POS Frontend`:

```powershell
npm.cmd test -- --testTimeout=20000
npm.cmd run build
node scripts/compact-ui-browser-smoke.mjs
```

Use `--before` only against a saved pre-change build; it replaces the baseline evidence. The Laravel API, migrations, permissions, financial calculations, routes and working database were not changed for this UI task.
