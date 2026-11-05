# UI/UX refinement — UX-0 audit

Completed 2026-11-04 before application edits. Sources: the user's [refinement plan](UNIVERSAL_POS_UI_UX_REFINEMENT_IMPLEMENTATION_PLAN.md), sidebar image, [design language](UNIVERSAL_POS_COMPACT_UI_UX_DESIGN_LANGUAGE.md), historical architecture guide, current React source, API consumers and compact UI verification artifacts.

The supplied plan's UX-0/UX-1 numbering is separate from dashboard D0–D7 and the backend blueprint. The existing React implementation remains the source of truth where historical Blade documentation differs.

## Findings and prioritized file-level changes

| Priority | Existing behavior / issue | Files | Proposed change | Risk and verification |
| --- | --- | --- | --- | --- |
| UX-1 | Sidebar is a flat list; business selection lives in the topbar and business identity repeats in the footer | `components/layout/navigation.js`, `AppSidebar.jsx`, `TenantSwitcher.jsx`, `Topbar.jsx`, `NavigationDrawer.jsx`, `layouts/TenantLayout.jsx`, `styles/shell.css` | Match the reference's quiet brand, business context card, grouped Workspace/Operations/Management navigation and rounded dark surface. Reuse the real membership switcher; retain all current authorized routes | Business switch and nested drawer focus must remain correct. Test permissions, membership failure/success, collapsed navigation, mobile drawer and long labels |
| UX-1 | Page search is an unexplained icon | `components/layout/NavigationFinder.jsx`, `styles/shell.css` | Give desktop navigation search a visible “Search pages” label and an accurately named dialog | Keep search local to permitted navigation; no new API calls |
| UX-1 | Dashboard scope appears separately from heading; optional profit availability competes with actionable primary metrics | `components/dashboard/PageHeader.jsx`, `KpiCard.jsx`, `features/dashboard/BusinessDashboard.jsx`, `StaffDashboard.jsx`, `styles/dashboard.css` | Connect the header with actual selected scope/date. Preserve exact values and show period context. Prioritize real owner receivables; keep unverified profit explicitly unavailable in supporting indicators | No additional financial payloads or calculations. Test zero/unavailable/error distinctions and personal/branch data contracts |
| UX-1 | Business quick actions precede alerts and approvals; manager approvals follow staff activity | `features/dashboard/BusinessDashboard.jsx`, `StaffDashboard.jsx` | Place existing actionable exceptions after the main analytical area, ahead of exploration/secondary activity | Preserve real destinations and all current permission gates; verify DOM reading order and restricted role views |
| UX-2 | Shared tables and forms already work; no universal column preferences/saved views exist | `components/ui/TableViewport.jsx`, `features/operations/OperationUI.jsx`, `WorkspaceForm.jsx`, `ProductForms.jsx` | Later phase: improve applied-filter/no-match clarity and form grouping. Optional table-only preference requires approval in the supplied plan | Preserve server pagination and current add/edit/delete actions. No preference or bulk-action API is assumed |
| UX-3 | POS already has server quotes, keyboard-wedge search, hold/recall and safe retry | `features/operations/PosPage.jsx`, `OperationUI.jsx`, held-cart components | Later phase: assess initial focus and cart/totals reachability | Do not change quote/payment/idempotency semantics or shrink touch targets |
| UX-4/5 | Owner, admin, manager and cashier dashboards have real APIs; later role dashboards, saved preferences and an action center are not complete | Dashboard capability consumers and existing D2–D4 APIs | Keep further roles and optional features as reviewed work | Do not infer support from role names or show invented widgets/counts |

Paths above are relative to `POS Frontend/src/` unless a document is linked.

## Baseline and constraints

- Existing theme, Inter, Remix icons and route/portal density are suitable and remain in place.
- Keep 22px page titles, 14px body, 13px tables, 36px management controls, 44px ordinary rows, 232/72px navigation and the 58px topbar target.
- Keep the comfortable selling desk and existing 64px cashier New Sale action.
- The reference's Main Store is an example. Display the authenticated active business; branch context remains the actual dashboard filter, not a fabricated global branch switch.
- Sales & receipts remains its working route. No separate Orders or universal Settings backend is inferred from the reference image.
- Platform access remains separate; no platform menu is fabricated from a tenant role name.
- Existing baseline screenshots were copied from the last verified compact build into [UX-1 before](design/refinement/ux1/before/). Application source had not changed since that build; intervening work was documentation only.
- No Laravel routes, financial rules, schema, role grants or working business records are part of UX-1.

## Phase sequence

UX-0 is complete. UX-1 is the first implementation slice authorized by the user's design request. The supplied plan asks to implement phases individually and request review after each; UX-2–UX-5 therefore remain subsequent review points. The optional table-density preference is not included in UX-1.

Follow-up on 2026-11-05: the user approved UX-1 and authorized UX-2 core refinements. [UX-2 is implemented and verified](UX2_REFINEMENT.md), with [review screenshots](design/refinement/ux2/index.html). UX-3 onward and the optional table-only preference remain outside that approval.

Subsequent approval: the user approved UX-2 and authorized [UX-3 cashier usability](UX3_REFINEMENT.md). Its implementation and evidence are recorded separately; later phases still require their own review.

Next approval: the user approved UX-3 and authorized [UX-4 capability-based dashboards](UX4_REFINEMENT.md). The registry, specialist summary views, shared widgets and tenant/branch/multi-role verification are recorded there. Optional UX-5 remains a subsequent review point.

Final refinement approval: the user approved UX-4 and authorized [UX-5 accessibility/performance](UX5_REFINEMENT.md). Safe navigation/dialog focus, titles, live result summaries, reduced motion, lazy routes and bounded dashboard requests complete that authorized slice. New persisted preferences, an action center and the separate dashboard/backend release phases remain outside it.
