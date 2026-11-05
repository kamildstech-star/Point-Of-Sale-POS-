# UX-1 — Dashboard hierarchy and navigation refinement

Implementation started 2026-11-04; final verification completed 2026-11-05. Implements the first UI phase after the [UX-0 audit](UI_UX_REFINEMENT_AUDIT.md), following the user's [professional refinement plan](UNIVERSAL_POS_UI_UX_REFINEMENT_IMPLEMENTATION_PLAN.md) and sidebar reference image.

This phase refines the existing application. It does not implement the later UX-2–UX-5 proposals or the pending dashboard D5–D7 phases.

Follow-up: UX-1 was approved. The separately authorized [UX-2 core listing/form refinement](UX2_REFINEMENT.md) now has its own implementation report and review gallery.

## Visible improvements

### Navigation

- Rounded dark sidebar with a quiet white Remix store icon and Universal POS wordmark, matching the supplied reference's visual treatment.
- Active business displayed in a real switcher below the brand. The existing membership dialog and server activation endpoint are reused.
- Workspace, Operations and Management groups contain only the already implemented destinations allowed by the current effective permissions. Empty groups disappear.
- Dashboard is the navigation label for the existing `/` route. Point of sale, Sales & receipts and other operational names retain their actual purpose.
- Active route uses a subdued surface, readable text and one accent indicator. Links retain `aria-current`; full names remain available through titles and accessible labels when collapsed.
- Business selection remains available as an icon in collapsed navigation. On smaller screens it is available in the topbar and inside the navigation drawer.
- Desktop topbar avoids repeating the sidebar business switcher and gives the permitted-page search a visible “Search pages” label. The search dialog is named “Go to page”. It searches navigation, not customer/product records.
- The footer shows the staff role and navigation collapse action; it no longer repeats the business name already shown above the menu.

The expanded/collapsed sidebar remains 232/72px wide. Its desktop surface has an 8px outer inset and a viewport cap, with internal menu scrolling when needed. The surface height follows its contents. The content offset includes that inset so the sidebar never covers the page.

The reference's Main Store is illustrative: this implementation displays the authenticated active business. It does not invent a global branch picker, an Orders backend or a universal Settings destination. Existing setup/activation/workspace-tool routes remain available under their current permissions.

### Dashboard hierarchy

- Shared headers present the selected branch/business scope, personal/branch activity meaning, reporting dates, currency and last-updated metadata.
- Owner primary indicators prioritize net sales, invoices, current receivables and stock attention, using the same existing response fields. Unverified gross profit remains explicitly unavailable among the supporting indicators.
- Admin composition retains its existing operational shift and purchase-order indicators. Cashier and manager compositions retain their existing authorized data contracts.
- Period KPIs display their reporting dates outside the optional definition disclosure. Current snapshots are labelled separately from reporting-period values.
- Primary analytical panels use a roughly 2:1 desktop layout. A single permitted panel fills the available width.
- The owner/admin Action required region precedes product exploration and quick actions. Manager approvals precede supporting staff activity.
- Existing alerts are ordered by supplied severity without modifying their source array or adding demo records.
- KPI surfaces use restrained borders and supporting icons instead of giving every primary metric a different colored top border.
- SVG chart geometry responds to the actual panel width, keeping tick labels readable in narrow panels. Original decimal strings and exact-value disclosures are unchanged; only mark placement uses numeric coordinates.

## Source changes

Paths in this table are relative to `POS Frontend/src/`.

| Files | Purpose |
| --- | --- |
| `components/layout/navigation.js`, `AppSidebar.jsx` | Permission-preserving grouping, active navigation and business context placement |
| `components/layout/TenantSwitcher.jsx`, `Topbar.jsx`, `NavigationDrawer.jsx`, `layouts/TenantLayout.jsx` | Reuse the existing switch flow across desktop, collapsed navigation and nested mobile dialogs |
| `components/layout/NavigationFinder.jsx` | Discoverable, accurately labelled navigation search |
| `styles/shell.css` | Reference-based sidebar surface, group spacing and responsive presentation |
| `components/dashboard/DashboardContext.jsx`, `PageHeader.jsx` | Shared actual scope/date/currency context |
| `components/dashboard/KpiCard.jsx` | Visible period/snapshot context and unchanged zero/unavailable semantics |
| `components/dashboard/AlertList.jsx` | Stable urgency ordering of real supplied alerts |
| `components/dashboard/TrendChart.jsx` | Responsive chart coordinates and readable labels |
| `features/dashboard/BusinessDashboard.jsx`, `StaffDashboard.jsx` | Reorder existing authorized information and actions |
| `styles/dashboard.css` | Analytical hierarchy, context styling and consistent section spacing |
| Relevant existing React test files | Navigation, nested dialog focus, business switch, data scope and reading-order regressions |

Current tokens, theme provider, fonts, icon library and route/portal density remain in place. Management controls retain their 36px target; the `/pos` selling desk retains its comfortable controls and the cashier dashboard retains its 64px New Sale action. No additional package dependencies were installed.

## Data and workflow boundaries

No Laravel controllers, routes, API contracts, financial formulas, migrations, role grants or working business data were changed. The five existing owner dashboard resources are still summary, sales trend, branch performance, top products and alerts. Header context consumes the current verified dashboard state rather than issuing a separate request.

Business switching calls the existing membership endpoints only when requested. A refused switch keeps the original business and the user's dialog selection. Successful switching refreshes the existing authenticated tenant context and replaces the old tenant page. Drawer/child-dialog cleanup restores focus and document interaction.

Charts continue to use server-provided series. Receivables, collections, taxes, return credits and revenue retain their distinct meanings. Personal cashier data remains personal even when extra reporting permissions exist. No placeholder platform counts, action-center notifications or new role widgets were added.

## Screenshots

Open the [UX-1 review gallery](design/refinement/ux1/index.html).

| View | Before | Updated light | Updated dark |
| --- | --- | --- | --- |
| Owner desktop | [Before](design/refinement/ux1/before/dashboard-light-1440.png) | [Light](design/refinement/ux1/after/dashboard-light-1440.png) | [Dark](design/refinement/ux1/after/dashboard-dark-1440.png) |
| Owner mobile | [Before](design/refinement/ux1/before/dashboard-light-360.png) | [Light](design/refinement/ux1/after/dashboard-light-360.png) | [Dark](design/refinement/ux1/after/dashboard-dark-360.png) |
| Product list shell | [Before](design/refinement/ux1/before/products-light-1440.png) | [Light](design/refinement/ux1/after/products-light-1440.png) | [Dark](design/refinement/ux1/after/products-dark-1440.png) |
| Collapsed sidebar | — | [Light](design/refinement/ux1/after/sidebar-collapsed-light.png) | [Dark](design/refinement/ux1/after/sidebar-collapsed-dark.png) |
| Mobile navigation | — | [Light](design/refinement/ux1/after/navigation-drawer-light.png) | [Dark](design/refinement/ux1/after/navigation-drawer-dark.png) |
| Manager dashboard | — | [Light](design/refinement/ux1/staff/manager-light-1440.png) | [Dark](design/refinement/ux1/staff/manager-dark-1440.png) |
| Cashier dashboard | — | [Light](design/refinement/ux1/staff/cashier-light-1440.png) | [Dark](design/refinement/ux1/staff/cashier-dark-1440.png) |

The baselines were copied from the previous verified compact build before application changes. All current browser previews use synthetic HTTP fixtures and perform zero writes to the working POS database.

## Verification

- **217 React tests passed across 20 files.** This includes the existing CRUD, validation, billing, checkout/hold, loading and theme regression suite, plus the new grouping, membership-switch, dialog and dashboard-order checks.
- **35 focused dashboard tests passed across three files after the responsive SVG refinement.** These cover shared chart/data behavior and the owner/admin/manager/cashier compositions.
- **117 UI browser checks passed**, producing **46 screenshots** for the shared shell, owner dashboard, product list/form, reports, checkout, collapsed navigation, switch dialog and mobile drawer.
- **36 cashier/manager browser regression checks passed**, producing **21 screenshots**. Combined browser coverage is **153 checks and 67 current screenshots**, plus six copied baseline screenshots.
- **Vite production build passed**, with no new dependencies. The main JavaScript bundle is approximately 499.80kB before gzip.
- **Zero working-database writes** and no Laravel/API/schema changes.

Sanitized evidence: [verification summary](audit/refinement-ux1-verification.json), [UI browser checks](audit/refinement-ux1-after.json), [staff browser checks](audit/refinement-ux1-staff-browser.json). Complete local React reports are under `docs/audit/private/`.

The checks cover both themes; widths 360/768/1280/1440/1920; effective zoom reflow at 125% and 200%; contrast; fixed sidebar behavior; collapsed navigation; real switch-dialog UI; refusal/success switching; mobile nested-dialog focus; section order; exact values; and retained checkout touch targets.

The staff browser regression also exercises cashier receipt links, hold/recall, fresh quotes, same existing checkout flow, no-shift and no-assignment states, optional widget retry and access revocation. Fixture tests do not constitute a fresh live-backend isolation audit or operation of physical scanner/touch hardware. Backend code was unchanged in this phase.

From `POS Frontend`, reproduce with:

```powershell
npm.cmd test -- --testTimeout=20000
npm.cmd run build
node scripts/refinement-ux1-browser-smoke.mjs
node scripts/refinement-ux1-staff-browser-smoke.mjs
```

## Review boundary and next work

The supplied plan §10 says to implement the phases individually, “requesting review at the end of each phase.” UX-1 is the concrete first phase for review. UX-2 listing/form refinement, UX-3 cashier workflow work, UX-4 additional role composition and optional UX-5 features remain subsequent phases. The optional table-only density preference specifically requires approval under §5.2 and has not been added here.
