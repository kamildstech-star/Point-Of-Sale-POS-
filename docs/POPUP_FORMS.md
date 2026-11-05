# Popup form behavior

Add/edit forms now open in centered modal dialogs over the current workspace. A shared component provides the same keyboard, validation and saving behavior across the application.

Pencil icons now open the same forms prefilled for editing; trash icons open confirmation popups. See [record actions](RECORD_ACTIONS.md) for archival/deactivation behavior and protected records.

| Area | Forms using dialogs |
| --- | --- |
| Products | Add/edit product, add/edit variant, image upload and archive confirmation |
| Catalog setup | Categories, brands, units, attributes/values, tax, price lists, barcodes and variant attributes |
| Business setup | Create business, branches and stock locations |
| Team | Add/edit member access and create/edit custom roles |
| Retail references | Customers, suppliers, registers and payment methods |
| Inventory | Adjustments, stock counts and reservations |
| Purchasing | Purchase order, goods receipt, supplier return and settlement |
| Cash | Open shift, cash movement and counted shift closing |
| Sales and returns | Later collections, return/cancellation requests and confirmed refunds |
| Expenses | Category and draft expense |
| Workspace tools | Receipt/return settings, alert template, CSV import/export and support case |
| Legacy setup | SKU-policy confirmation, stock mapping, reviewed customer link and POS activation |

The Add product button opens a dialog on the catalog list. The existing `/catalog/new` URL also opens that dialog over the catalog. Product details remain a readable page with buttons to open their editors. Search, report filters, login/registration and the main POS cart retain their normal page layouts.

## How to use

1. Click the relevant Add/Edit/action button. The form appears over the page.
2. Complete the fields and save. A successful save closes the editor or shows its existing successful result and refreshes the affected data.
3. Validation errors remain inside the popup with entered values retained.
4. Use Cancel, the close button or Escape to dismiss an idle editor. Focus returns to the opener when it is still present.
5. While a save is in progress, submission and dismissal controls are locked. A failed save unlocks the editor for correction/retry.

Background controls are made inert and hidden from assistive navigation while the modal is open. Tab/Shift+Tab stay inside the current popup; nested popups restore the previous layer correctly. Background scrolling is locked. Long forms scroll inside the panel, and narrower viewports use a smaller margin and a single-column form layout.

## Verification

- Frontend regression: **47 tests passed**, including product creation from the list, category parent/slug behavior, supporting add-form flows, cancellation without posting, validation retention, focus management, nested layers and save-in-progress protection.
- Production Vite build passed without duplicate JSX attribute warnings.
- A headless Chrome smoke check used a private local fixture API, with **no real Laravel/POS database writes**. It verified a single product creation, preserved decimal price, list refresh, category slug generation, validation inside the popup, keyboard trapping and focus/scroll restoration.
- Chrome viewport checks passed at **1440 × 1000 desktop** and **390 × 844 mobile**. Private screenshots/evidence are under `docs/audit/private/modal-*` and are excluded from source control. This fixture check does not replace live backend/Stripe or physical hardware acceptance.

Manual acceptance: open and cancel the editors listed above; save a valid record; exercise a duplicate/invalid value; retry it; use Escape and keyboard navigation; inspect a long form on mobile. Continue the broader [UAT plan](UAT_TEST_PLAN.md). Existing manual CSV results are not marked passed by the automated checks.

## Implementation notes

`Modal.jsx` owns the portal, dialog label, focus/background isolation, scroll locking and submission context. `FormDialog` adds a trigger and closes after a successful `SaveForm`. `SaveForm` captures values before locking controls, prevents duplicate local submission and keeps server errors in place. An `onSave` callback must reject/throw on failure so the dialog does not close prematurely. Product creation/editing share the same field/payload builder in `ProductForms.jsx`.

The change updates frontend presentation and form handling; it does not run migrations, seeders, branch activation or billing operations.
