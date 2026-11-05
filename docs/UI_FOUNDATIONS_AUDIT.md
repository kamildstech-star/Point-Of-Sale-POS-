# UI-0 inspection and UI-1 scope

Inspected 2026-11-02 against [the supplied design guide](UNIVERSAL_POS_UI_UX_THEME_IMPLEMENTATION_GUIDE.md). The guide's section 12 authorizes inspection/reporting followed by **UI-1 only**. UI-2 through UI-7 remain separate work. The existing roadmap's numbered backend phases are distinct from these UI phases.

## Architecture and compatibility

`docs/AGENT.md` is the historical Blade audit. Current architecture is documented in [IMPLEMENTATION.md](IMPLEMENTATION.md), [PHASE_2_3.md](PHASE_2_3.md), [PHASE_4_5.md](PHASE_4_5.md) and [PHASE_6_7.md](PHASE_6_7.md).

- React 18.3 JavaScript/JSX; Vite 6; React Router 6. No TypeScript source/configuration is needed.
- Existing Remix Icon 4.9, Toastify 11, Axios and custom CSS. Tailwind, shadcn and chart libraries were absent at inspection.
- Existing shared components: `Modal`/`FormDialog`, `SaveForm`, validated `Input`/`Select`, `LookupSelect`, `RecordActions`/`DeleteDialog`, `RemoteTable` and operation controls. Preserve focus trapping, inert backgrounds, error placement, native field semantics and submission locking.
- Laravel session cookies/CSRF and server-resolved tenant context remain authoritative. React route permissions complement backend authorization. The route outlet remounts on a business switch.
- Use Tailwind 4's Vite integration without Preflight to avoid resetting established forms/tables. Add local JavaScript shadcn-style native primitives; do not generate TypeScript or install another icon system.
- Self-host Inter. Keep stylesheet colors semantic, including modal portals and Toastify's separate portal host.
- Preserve the user's existing menu-height sidebar, fixed desktop position, internal overflow and compact sticky mobile navigation. Drawer/collapsed-shell work belongs to UI-2.
- Existing uncommitted work and root-level historical moved-file deletions are outside this change. No migrations, database reads/writes, seeders, credentials or backend code changes are required.

## Page migration matrix

All React routes receive shared token/control presentation in UI-1. The target layouts below remain future work.

| Current route | Current foundations | Target / phase |
| --- | --- | --- |
| `/login`, `/register` | SaveForm and validated business/staff fields | Shared controls now; auth polish later |
| All protected routes | Inline Layout, sidebar/topbar, permission-filtered Remix links | AppShell/Sidebar/Topbar/selectors, UI-2 |
| `/` | Welcome banner and status cards | Real KPI/chart dashboard, UI-3 |
| `/workspace`, `/select-business` | Membership cards, modal/forms | Membership selector UI-2; tenant presentation UI-6 |
| `/catalog`, `/catalog/new`, `/catalog/:productId` | Product forms, modals, record actions, lookup/table | Sectioned editor and product tables, UI-5 |
| `/setup/:resource` | Reference tables and shared add/edit dialogs | Setup/module presentation, UI-5 |
| `/team` | Staff/role tables, permission checkboxes, dialogs | Staff/settings presentation, UI-5 |
| `/legacy` | Mapping, evidence, paginated exceptions and approvals | Preserve specialist migration workflow, UI-5 |
| `/inventory` | RemoteTable, LineEditor, ProductFinder, dialogs | Inventory filters/documents, UI-5 |
| `/purchasing` | Tables, lines and receiving/settlement dialogs | Purchasing documents/details, UI-5 |
| `/operations/:resource` | Customer/supplier/register/payment-method directories | Directory/detail presentation, UI-5 |
| `/pos` | Search/scanner, cart, server quote, payments | Product/cart/payment refinement, UI-4 |
| `/cash` | Register tables, opening/movement/closing dialogs | Cash reconciliation presentation, UI-5 |
| `/sales`, `/sales/:saleId` | Sales table, printable receipt, payment dialog | Sales filters/details, UI-5 |
| `/returns`, `/returns/new/:originalId`, `/returns/:returnId` | Original sale, approval/post/refund/exchange | Return timeline/document/status, UI-5 |
| `/expenses` | RemoteTable, category/expense dialogs | Expense presentation, UI-5 |
| `/reports` | Date form, real financial summaries and tender panels | Report charts/tables/filters, UI-5 |
| `/pos-settings` | Activation preview and reference controls | Settings presentation, UI-5 |
| `/workspace-tools` | Settings/templates, alerts, import/export, audit/support | Tools/settings presentation, UI-5 |
| `/billing/*`, `/pricing` | Real plans/subscription/usage/invoices, Checkout/Portal | Billing refinement, UI-6 |
| `/forbidden`, unmatched routes | Shared notice/card | Shared error/empty-state foundations, UI-1 |

Legacy Blade remains separate: admin shell; staff/role/permission and school/product/size/stock CRUD; orders/invoices/returns; sales/return/stock/PDF reports; dealer ledger/daily accounts/expenses; public storefront/account/cart pages. Copied AdminLTE demo pages are not implemented business features. Existing local Remix compatibility assets remain; React's theme and Tailwind are not injected into Blade.

## Real API support and future gaps

| Proposed feature | Existing API | Gap / constraint |
| --- | --- | --- |
| Business selector | Memberships, tenant switch/context | No new API needed; show authorized memberships |
| Branch selector | Tenant catalog branches/locations; report branch filter | No universal branch session API; POS branch follows register |
| Notifications | Stored workspace alerts and mark-read | No WebSocket or unread-summary endpoint |
| Search | Per-resource products/catalog/directories; POS search/scan | No permission-aware global cross-module endpoint |
| Dashboard | Reports summary: financial totals/counts, tender totals, balances/reconciliation | No time series, period comparisons, category breakdown, popularity or customer growth API |
| Profit | Reported stored-cost estimated margin | Do not label as verified profit/COGS or invent trends |
| Inventory | Balances, movements, adjustments/counts/reservations/reconciliation | No stock transfer workflow; batch/serial posting not active |
| POS | Server quote, idempotent checkout, sale/payments and return-based exchanges | No hold/recall API; inventory reservations are not held carts |
| Billing | Plans/subscription/usage/invoices, Checkout/Portal, signed webhooks | Live charging remains disabled/configuration-gated |
| Platform admin | No current cross-tenant admin API | Needs separate authorization/API before UI-6 implementation |

## Exact UI-1 file plan

- Update `POS Frontend/package.json`, `package-lock.json`, `vite.config.js`: pinned Tailwind/Vite plugin and local Inter package.
- Update `src/main.jsx`, `src/app/App.jsx`: stylesheet/font startup, theme bootstrap/provider and a toggle in the existing user controls.
- Add `src/theme/preference.js`, `ThemeProvider.jsx`, `ThemeToggle.jsx`: light default; explicit light/dark preference per authenticated user and browser; guest preference separate; safe storage failure and cross-tab synchronization. No profile API change.
- Update `src/icons/catalog.js`: named Remix sun/moon icons. Existing build regenerates the local Blade icon assets/license from the registry.
- Add `src/styles/tokens.css`, `tailwind.css`, `foundations.css`; update `app.css`, `modal.css`, `notifications.css`. Keep `layout.css` and navigation behavior.
- Add `src/components/ui/primitives.jsx`; reuse these within `WorkspaceForm.jsx` and existing application states without changing form submission or validation contracts.
- Add theme/foundation regression tests; extend the private browser harness with both-theme previews, persistence, focus/contrast and responsive checks.
- Save the supplied guide here, this audit, and a UI-1 implementation/verification document; link them from `IMPLEMENTATION.md`.

No dashboard/POS/module restructure, new analytics, backend, route/payload, financial arithmetic, Stripe activation or data migration is included.
