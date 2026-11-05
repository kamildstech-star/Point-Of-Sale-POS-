# Form validation

The current React workspace now uses JavaScript validation before API calls and Laravel validation on the server. HTML attributes remain useful input hints, but browser validation bubbles are disabled with `noValidate` so errors appear consistently beside fields.

Validation and request failures also appear through the [global toast notifications](GLOBAL_NOTIFICATIONS.md). Persistent summaries stay at the top of forms and field errors remain beside their inputs.

Coverage includes sign-in, registration, business creation, team access and roles, products and variants, images, all catalog reference forms, branches and locations, inventory, purchasing, POS quotes and checkout, collections, returns and refunds, cash shifts, expenses, reports, legacy mapping confirmations, and workspace settings, templates, imports, exports and support. Billing selections continue to use configured server plans and the existing Laravel authorization and Checkout validation.

The preserved legacy Blade screens retain their existing validation. This change applies to the React workspace and its Laravel API; it does not migrate or replace historical Blade modules.

## User behavior

- Required values, optional values when supplied, lengths, email addresses, slugs, currency codes, time zones and integer ranges are checked on blur and submission.
- Password confirmation, required review checkboxes, date ordering and the 366-day report range are checked in JavaScript as well as Laravel.
- Decimal inputs reject exponent notation, commas, unsupported signs, excess digits and excess decimal places. Operational values support up to 12 integer digits and four decimal places; catalog prices retain their existing 15-digit/four-place contract. Payment and cash amounts also respect the supported currency's minor-unit precision.
- Orders require 1–100 lines, reservations exactly one variant, and receipt/return forms at least one positive selected quantity. Receipt and return quantities are checked against the displayed remaining quantities. The server checks current quantities again.
- Split payments require active distinct methods, positive amounts and confirmed external references for card/bank payments. The client checks the amount due and the full-payment policy shown by the server quote. No floating-point arithmetic is used for those comparisons.
- Uploads have a client format/2 MB check. Laravel separately checks file size, MIME/content and image dimensions; CSV row validation still runs on the server.
- Invalid client submissions make no save request. The error summary appears at the top of each form, above the fields. Field errors use `aria-invalid` and `aria-describedby`, and submission focuses the first invalid field. Laravel HTTP 422 errors are associated with matching fields; operation and other unmatched errors remain visible in the summary.
- Popups keep entered values on failure and close after a successful save. Busy-state and duplicate-submission protection remain enabled.

Uniqueness, tenant-scoped references, permissions, subscription limits, stock availability, unit quantity precision, stale quotes and financial posting rules remain authoritative in Laravel and its transaction-protected services. The browser cannot establish those conditions safely by itself.

## Implementation

- `POS Frontend/src/validation/forms.js`: shared application rules, date/password checks, exact decimal comparisons and order/payment checks.
- `POS Frontend/src/components/WorkspaceForm.jsx`: `SaveForm`, accessible field feedback and Laravel error handling. Controlled inputs use `ValidatedControl`; `LookupSelect` participates too.
- Forms can supply a `rules` object keyed by input name and a `validate(FormData, existingErrors)` callback returning a field/message map. New forms should use these components and define both client and Laravel rules. Do not add new business rules solely in React.
- Nested line/payment controls use API-style dotted names. Product creation maps `variant.*` errors to its `variant_` controls. Other unmatched server errors are retained rather than hidden.
- `POS backend/app/Rules/DecimalInput.php`: reusable decimal-string validation on inventory, purchasing, POS and finance requests. Existing service-level checks remain in place.
- Laravel additionally handles duplicate team membership as an email validation error, malformed authentication identities before throttling, nested product variant errors, report ranges, template tokens and image dimensions. Valid template token whitespace is normalized before storage.

No database migration or seeder is needed for this change.

## Verification and manual checks

The [verification record](audit/form-validation-verification.md) contains the final test counts and desktop/mobile browser results.

Automated cases are in `src/test/Validation.test.jsx`, the existing React workflow tests, and `tests/Feature/FormValidationTest.php`. The API cases bypass the UI and assert that rejected input does not create financial documents or alter stock.

Run from separate project directories in PowerShell:

```powershell
Set-Location 'POS Frontend'
npm.cmd test -- --testTimeout=15000
npm.cmd run build
```

```powershell
Set-Location 'POS backend'
php artisan test --do-not-cache-result
```

Laravel tests assert an isolated in-memory SQLite connection; they do not use the existing business database. Existing UAT CSV results have not been changed.

On a disposable UAT copy, exercise these variations on each relevant form:

| Scenario | Expected result |
| --- | --- |
| Submit an empty form or whitespace-only required text | Inline required errors; no save request; popup stays open |
| Fill an optional email with an invalid address | Inline format error; blank remains allowed |
| Enter `-1`, `1e3`, `1,000` or `1.12345` in an unsigned decimal field | Client blocks unsupported formats; direct API requests also fail |
| Enter a fractional unit precision, 366 supplier terms, negative return days or an invalid time zone | Client and server return errors |
| Mismatch password confirmation or leave a review checkbox unticked | Submission blocked |
| Set an end date before its start or a report range above 366 days | Error identifies the end date |
| Save a product with a duplicate SKU/category with a duplicate slug | Laravel rejects it; matching input shows the error and values remain |
| Save an empty order, zero-only receipt/return, or quantities above the remaining amount | Client blocks; server independently enforces current limits |
| Duplicate a payment method, omit an external reference, overpay, or underpay a walk-in | Submission blocked; a valid split payment can still complete |
| Upload an oversized or unsupported image/CSV | Client blocks; bypassing it still invokes server checks |
| Correct invalid values and retry | Stale field feedback clears and a valid save succeeds once |
| Test on a small screen and use keyboard navigation | Errors stay inside the scrollable dialog; focus stays accessible |

The local Chrome verification uses fixture API responses rather than the working Laravel database. It checks desktop/mobile popup bounds, client required/decimal failures without POSTs, a successful retry with the exact decimal price, Laravel-style duplicate slug feedback, and focus/scroll restoration. Private screenshots and the execution report are stored under `docs/audit/private/`.
