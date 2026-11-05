# Record action verification — 2026-10-26

Scope: edit/delete icons, shared prefilled editors and confirmed removal for editable catalog, setup, retail reference and team records. No working POS records, environment files, seeders or credentials were changed. No migrations or dependency installations were needed.

| Check | Result |
| --- | --- |
| Full React regression | 124 tests passed in 10 files |
| Final catalog/setup/modal regression after state handling changes | 39 tests passed in 3 files |
| Full Laravel regression | 85 tests passed, 1,470 assertions |
| Final Laravel validation/tenancy/action regression after shared deactivation guard | 31 tests passed, 585 assertions |
| Production Vite build | Passed; 129 modules, JS 396.51 kB / 122.71 kB gzip |
| Final headless Chrome fixture on rebuilt frontend | Passed at 1440 × 1000 and 390 × 844 |
| Tracked-file whitespace check | `git diff --check` passed |

`RecordActions.test.jsx` adds 14 cases covering prefilled product editing without duplicate variants, confirmed archive and canceled deletion, refusal errors at the top of the popup, updating/deactivating all four retail reference types, variant decimal preservation, last-page correction after deletion, tax retention, read permissions, membership suspension/restoration, custom roles and accurate global success wording.

`RecordActionsTest.php` adds 10 cases covering history-preserving updates/archives, variant/product ownership, catalog leaf deletion and assigned attribute protection, customer/supplier/payment history, open-register protection through DELETE and PATCH, branch/location stock and shift checks, assigned/system roles, the final owner, cross-business shared identities, foreign-tenant IDs, reader permissions and internal exchange credit protection. The final targeted run includes the additional assertions for branch/location deactivation through PATCH. Tests assert their SQLite `:memory:` fixture database before building it; they do not migrate or seed the working POS database.

The browser fixture checks actual pointer clicks on the icons, current values in the shared popup, exactly one PATCH and DELETE for a product, cancellation without a DELETE, successful list refresh, category slug preservation while editing, category archival, global success/error messages and keyboard focus/scroll restoration. Existing required-field and decimal-validation checks remain enabled. The build was checked again after the final setup state and icon-spacing changes.

Private browser evidence is in `docs/audit/private/modal-browser-verification.json`. Screenshots include `actions-product-edit-desktop.png`, `actions-product-delete-desktop.png`, `actions-product-list-desktop.png`, `actions-category-edit-mobile.png`, `actions-category-delete-mobile.png` and `actions-category-list-mobile.png`. The screenshots were visually inspected. Chrome used its own hidden headless process and temporary profile against a synthetic local API; that process and fixture server exited successfully. No browser request wrote to Laravel or Stripe.

The first focused frontend run encountered a cold-start query timeout in its initial test. The initial query now has an explicit five-second wait; the subsequent full suite and final focused suite passed. Test bodies continue to use the normal save/error assertions rather than ignoring failures.

Actions preserve the existing immutable transaction workflows. Archived/inactive records remain visible for history and restoration. Tax rates retain effective-date editing, system roles cannot be removed, and a used branch/location/register cannot be deactivated until its operational conditions are resolved.

See [RECORD_ACTIONS.md](../RECORD_ACTIONS.md) for behavior and manual acceptance scenarios. Existing UAT CSV results were not changed; browser fixtures and automated tests do not mark those manual cases as passed.
