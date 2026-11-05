# UX-2 — Core listing and form refinements

Implemented following the user's approval of UX-1 and authorization to proceed with UX-2 core refinements. This follows the [refinement plan](UNIVERSAL_POS_UI_UX_REFINEMENT_IMPLEMENTATION_PLAN.md), building on [UX-1](UX1_REFINEMENT.md).

## Visible changes

### Lists and filters

- Products, catalog/business setup and all lists using `RemoteTable` share the same pagination layout. It displays the server's current page, last page, record range and total. If a response lacks range metadata, it accurately states how many records are on that page.
- Initial navigation requests page one. Previous/Next request the corresponding server page. The browser does not fetch the entire dataset to paginate it locally.
- Paging controls are locked during a request or failed refresh. Existing rows are temporarily inert while loading or in an error state, preventing actions on stale results.
- A changed resource/filter path remounts the remote list at page one. Late responses from the previous scope are ignored. Existing last-page correction after deletion is retained.
- Applied product searches and dashboard drill-down filters are visible as individual chips. Clear filters uses the owning page's existing route/action. An explicit `own=1` personal sales/shift view is retained; a personal-only view has no misleading clear action.
- Empty collections, filtered lists with no matches, loading and failures have distinct presentations. A failed request offers retry without changing its query. A product search with no results no longer tells the user to create their first product.
- Core list headers stay visible inside a bounded table scroll area. Headers, table contents and horizontal scrolling stay together; the page itself can still scroll.
- Scrollable table regions remain keyboard reachable and explain horizontal/vertical overflow. All columns and original values remain present.
- Money and quantities retain existing right alignment and tabular digits. Names, SKU strings, phones and identifiers retain their complete values and appropriate alignment.

These shared changes reach Products, setup references, Inventory, Purchasing, Sales, Cash, Customers/Suppliers and other existing `RemoteTable` consumers. Lists that use their own specialized pagination retain that implementation.

### Forms and dialogs

- Product creation is grouped into Product details, First variant, Pricing, and Inventory & availability. Standalone variant dialogs share the same identity and pricing sections.
- Brand/description and advanced variant settings use native expandable sections. Their controls remain mounted, so closing a section preserves its values and includes them in submission.
- Client/server validation opens every affected optional section. Focus moves to the first invalid field after revealing its ancestors. The error summary remains at the top of the form, with individual field errors.
- The dialog focus loop includes disclosure headings, allowing keyboard users to expand a section and continue into its fields.
- Inventory documents separate Stock document and Items. Draft purchase orders separate Order details and Order items. Existing fields, line editing and confirmation workflows remain in their original dialogs.
- The two-column management layout becomes one column on mobile. Existing sticky dialog actions, busy locks, cancel behavior, focus restoration and density inheritance remain in use.
- Product detail and editor modules load on demand using the existing loading components, keeping their code out of the initial application bundle.

## Scope and compatibility

No Laravel endpoint, schema, role grant, financial calculation or stock-posting rule was changed. No dependency was added. Existing product and variant request fields, checkbox defaults, decimal strings, export behavior and create/edit/delete API calls remain intact. All browser previews use synthetic HTTP fixtures and perform zero writes to the working POS database.

The optional table-only density preference was not part of this approval. Saved views, column preferences, bulk actions and new sorting/filtering APIs are not represented as implemented. `/pos` workflow improvements remain UX-3 work.

## Source changes

Paths are relative to `POS Frontend/src/`.

| Files | Purpose |
| --- | --- |
| `components/ui/ListPatterns.jsx` | Shared pagination, filter chips and empty states |
| `components/ui/TableViewport.jsx`, `styles/foundations.css` | Sticky headers, measured overflow, keyboard scrolling and semantic styling |
| `features/CatalogPage.jsx`, `ReferencePage.jsx` | Product/setup listing integration, search context and server counts |
| `features/operations/OperationUI.jsx` | Shared remote list page reset, retry, stale response protection and paging locks |
| `features/operations/DashboardDrilldown.jsx`, `SalesPage.jsx` | Filter chips and preservation of explicit personal views |
| `components/WorkspaceForm.jsx`, `Modal.jsx` | Reusable sections/disclosures, error revelation and dialog keyboard focus |
| `features/ProductForms.jsx`, `ProductPage.jsx`, `app/App.jsx` | Grouped shared product/variant dialogs and loading modules on demand |
| `features/operations/InventoryPage.jsx`, `PurchasingPage.jsx` | Stock/order form grouping and descriptive list regions |
| `test/ListingForms.test.jsx`, `Modal.test.jsx`, `DashboardDrilldowns.test.jsx` | Behavior and regression coverage |

## Review evidence

Open the [UX-2 screenshot gallery](design/refinement/ux2/index.html). Before images were copied from the verified UX-1 build before source edits. The fixture dataset is larger in the UX-2 preview to exercise multi-page lists and scrolling.

Verification results are recorded in [the verification summary](audit/refinement-ux2-verification.json). Browser reports include requests, assertions, screenshots and measured dimensions; complete local React reports are under `docs/audit/private/`.

Final verification on 2026-11-05:

- **229 React tests passed across 21 files**, including the existing catalog, CRUD, permissions, billing, validation, loading, dashboard and checkout/held-cart tests.
- **185 core browser checks passed**, producing **82 screenshots** for Products, product dialogs, Inventory, stock document dialogs, purchase orders/dialogs, setup, Sales and Customers.
- **36 cashier/manager browser checks passed**, producing **21 screenshots**. These exercise receipt navigation, hold/recall, fresh quotes, completed checkout, dialog density, no-shift/no-assignment views, loading, retry and access revocation.
- Combined browser evidence: **221 checks and 103 current screenshots**, plus **six copied baseline images**.
- **Vite production build passed**. Main JavaScript is approximately **494.97kB** before gzip, below the default 500kB chunk warning; product detail/editor code loads separately.
- Both themes; widths 360/768/1280/1440/1920; effective zoom reflow at 125% and 200%; contrast; desktop compact sizes; mobile touch targets; non-fragmented mobile headings; sticky table header geometry; exact values; server paging; empty/error/retry; optional-field validation and keyboard disclosure navigation were checked.

The two browser reports were refreshed after the final CSS build. The evidence generator rejects reports older than that build.

From `POS Frontend`, reproduce with:

```powershell
npm.cmd test -- --testTimeout=20000
npm.cmd run build
node scripts/refinement-ux2-browser-smoke.mjs
node scripts/refinement-ux2-staff-browser-smoke.mjs
node scripts/record-ux2-verification.mjs
```

The browser harness needs local Chrome and uses only its own HTTP fixture server. It does not connect to the configured Laravel database or verify physical scanner hardware. Existing unit tests cover API calls and permission-sensitive UI, but this UI-only phase is not a new live-backend isolation audit.

## Review boundary

The supplied plan §10 requests implementing each phase individually, “requesting review at the end of each phase.” UX-2 core refinements are the current review slice. UX-3 cashier productivity, UX-4 further role composition and optional UX-5 work remain subsequent phases. The separate pending dashboard D5–D7 phases are not advanced by this change.

Follow-up: the user approved UX-2 and authorized [UX-3 cashier usability](UX3_REFINEMENT.md), documented separately with its own review gallery.
