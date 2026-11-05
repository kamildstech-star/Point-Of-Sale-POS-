# Form validation verification — 2026-10-22

Scope: current React workspace forms and their Laravel API. See [FORM_VALIDATION.md](../FORM_VALIDATION.md) for behavior and manual scenarios.

| Check | Result |
| --- | --- |
| Full Laravel suite | 75 tests passed, 1,242 assertions |
| Final Laravel validation/workspace rerun after template whitespace normalization | 12 tests passed, 319 assertions |
| Final React suite | 88 tests passed across 8 files |
| Production frontend build | Passed, 120 modules transformed |
| Desktop Chrome, 1440 × 1000 | Passed |
| Mobile Chrome, 390 × 844 | Passed |
| Tracked-file whitespace check | `git diff --check` passed |

The added API tests bypass client validation. They exercise malformed onboarding input, nested product errors, duplicate team membership, role prerequisites, reference fields, decimal inventory/purchase lines, invalid quotes and payments, cash/expenses/returns, workspace/report validation and invalid uploads. Rejected requests are checked for absence of unintended database writes. The Laravel fixtures assert SQLite `:memory:` before creating test tables.

React checks include invalid programmatic submission with browser constraints bypassed, required and optional values, formats/ranges, password confirmation, review acceptance, dates, files, nested Laravel error mapping, retained values and server errors, exact decimal comparisons, payment constraints, empty purchase orders, invalid POS quantities, modal behavior and normal save flows.

The Chrome run uses a local fixture HTTP server serving the production build. It does not write to the working Laravel database. It verified:

- Empty product/category forms and an exponent-style product price do not issue save POSTs.
- Both required category fields are identified on the first submit click.
- Focus moves to the first invalid field.
- Corrected product submission succeeds once with `12.3456` preserved as a string.
- A Laravel-style duplicate slug error appears beside the slug with its accessible description; the popup retains entered values.
- Dialogs fit desktop/mobile viewports, trap keyboard focus and restore background scrolling and opener focus after closing.

Private evidence: `docs/audit/private/modal-browser-verification.json`, `validation-product-desktop.png`, `validation-category-mobile.png` and the corresponding popup screenshots. These fixture checks do not replace the manual business UAT pack, and no CSV results have been marked passed.

No application environment, credentials, migrations, seeders or existing POS records were changed for this work. PHPUnit was run outside the sandbox after a sandbox result-cache write denial; its in-memory database isolation remained enforced. Frontend tests used a 15-second per-test allowance for this Windows environment.
