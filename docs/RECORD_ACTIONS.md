# Edit and delete actions

Updated 2026-10-26. Editable list rows use pencil and trash icons with accessible labels and hover descriptions. The pencil opens the existing Add form with the selected record's values. Saving uses PATCH for that record; it does not create another record. Products can now be edited directly from the catalog list as well as their detail page.

The pencil, trash and popup controls now use the shared [Remix Icon integration](REMIX_ICONS.md).

The trash icon opens a confirmation popup identifying the selected record. Cancel, Close and Escape send no deletion request. During submission the popup is locked against duplicate submission and dismissal. Failures keep the popup open with the error at the top and a global error notification. Successful changes refresh the list and announce the result through React Toastify.

## Supported records

| Page or record | Edit behavior | Delete behavior |
| --- | --- | --- |
| Products | Shared product popup, with current product details loaded from Laravel | Archives the product and every variant |
| Product variants | Shared variant popup, retaining decimal cost/price strings | Archives the selected variant |
| Categories, brands, units, attributes, tax classes, price lists | Existing setup popup | Archives the record |
| Attribute values | Existing setup popup | Deletes an unused value; assigned values return a clear conflict |
| Barcodes, variant attribute assignments, price list items | Existing setup popup | Deletes the selected metadata entry |
| Tax rates | Existing setup popup, including effective dates | Retained for history; trash icon disabled with an explanation |
| Branches, stock locations | Existing setup popup | Deactivates the record; refuses while stock, reservations or register shifts remain |
| Customers, suppliers | Same popup used for Add | Deactivates the record and preserves transaction references |
| Registers | Same popup used for Add | Deactivates only after its open shift has been closed |
| Payment methods | Same popup used for Add | Deactivates external payment methods; internal exchange credit is managed by returns |
| Team members | Same member-access popup, editing business role/status | Suspends access to the current business; preserves the shared account and other memberships |
| Custom roles | Same role popup, prefilled permissions | Deletes only unassigned custom roles, including checking suspended memberships |

Archived and inactive rows remain visible for history and restoration. Their trash icon is disabled; use Edit to restore Active/Status. Restoring an archived product also requires restoring the desired variants in its detail page. An archived unit or another inactive dependency may also need restoration before a product can be sold.

Posted financial and stock records keep their existing review, approval, return, refund, cancellation and reversal workflows. Sales, payments, ledger movements, invoices, receipts and audit history are not given generic edit/delete controls. Team access editing deliberately does not modify a staff identity's shared name, email or password across businesses.

## Backend protection

All actions resolve the tenant from the authenticated server context and enforce the existing manage permission for that resource. DELETE operations never accept a client tenant override. Variant deletion checks the parent product as well as tenant ownership. Writes run in transactions under the tenant lock and record audit events. System roles and the last active owner are protected. Branch/location and open-register deactivation checks also apply when changing Status/Active through Edit.

New routes follow the existing `/api/v1` conventions: DELETE `operations/{resource}/{id}`, `products/{product}/variants/{variant}`, `team/{member}` and `roles/{role}`. Product/reference DELETE and all corresponding PATCH routes are reused. No new dependencies or database migrations are required for this change.

Server pagination remains in place. Deleting the final removable entry on the last page returns the list to its new last page. Changing catalog setup tabs resets that resource's editor and pagination; responses from a previous resource cannot populate the current list.

## Manual acceptance scenarios

Use a disposable UAT database for these checks. Existing [UAT CSV results](UAT_TEST_CASES.csv) are unchanged.

1. Open product Edit directly from the list. Confirm the name, category, description and checkboxes are prefilled. Rename it, save, reload and verify the change. Confirm no extra product or variant was created.
2. Open Add and Edit for a category, customer and supplier. Confirm the same popup fields are used, existing values are retained and saving updates only the selected row.
3. Rename a category and confirm its existing slug is retained. Trigger a duplicate slug and verify the server error stays at the top of the popup and beside the slug field.
4. Click each trash icon. Verify the confirmation identifies the intended record and explains archival, deactivation or permanent removal. Cancel and verify no DELETE request or row change occurred.
5. Confirm product archival. Verify product/variant status changes while old stock, sale and invoice data remain readable. Restore product and variant using Edit and recheck the POS lookup.
6. Deactivate a customer or supplier with history. Verify its old transactions remain readable. Restore it with Edit.
7. Try deleting/deactivating a register with an open shift. Verify a clear refusal, an open popup and unchanged register status. Close the shift and retry.
8. Try deactivating a branch/location with stock or an open shift, through both Delete and Edit. Verify refusal. An empty, unused location can be deactivated and restored.
9. Delete a barcode or price-list item. Verify it disappears. Delete the sole remaining row on page 2 and confirm pagination returns to page 1.
10. Try deleting an attribute value assigned to a variant. Verify refusal. Remove its variant assignment and retry.
11. Verify tax rate deletion is disabled and its end date remains editable.
12. Remove a team member's access to this business. Verify their next request is denied while their other business membership remains active. Restore access with Edit access.
13. Try removing the final owner, deleting a system role and deleting an assigned custom role. Verify refusal. Reassign all members, then delete an unused custom role.
14. Sign in as a reader. Verify edit/delete controls are absent and direct PATCH/DELETE requests are denied. Repeat with IDs from a different tenant; no foreign record may change.
15. Try invalid inputs, a failed save, rapid duplicate clicks, keyboard navigation and mobile layouts. Verify top-of-form errors, preserved input, a single successful write, focus containment and success/error notifications.

Automated results and browser evidence are recorded in [the verification note](audit/record-actions-verification.md).
