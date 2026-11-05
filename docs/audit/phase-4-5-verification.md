# Phase 4–5 local verification

Date: 2026-10-19. Database: local MariaDB 10.4.32; Laravel 11.8.0, PHP 8.2.12 with BCMath; React 18 / Vite JavaScript. No remote staging server is available.

Encrypted pre-change backup `backup_a901461149b848039d122178dc4d03a1` restored successfully to isolated `bu_restore_9bd573af3ad2374d`. Restore verified schema/counts, captured business/stock/financial fingerprints and 2,877 archived files. Backup keys and raw source reports remain private and ignored.

Post-implementation backup `backup_ca9edce0f1344a218714cf08405a6600` also restored successfully to isolated `bu_restore_0d8e284c473d0bfa`. It verified all new table DDL/counts, captured row fingerprints, stock/financial totals and 2,896 Laravel application/config file checksums. The source database was not used as a restore target.

`apply-phase-4-5.php rehearsal` successfully applied the new forward-only migration and initialized permissions, methods and safe legacy customer links on that verified restore. Before/after fingerprints of every original legacy business table, users, roles and permission rows matched. The verified connection uses its own name plus a rebound Schema facade. `apply-local` subsequently succeeded with matching rehearsal/concurrency evidence; original history and `.env` remained unchanged, and zero branches were activated.

The final source audit contains 80 tables and 49 recorded migrations. Legacy stocks, new sales and stock movements remain empty. Composer validates with the explicit BCMath requirement, new PHP files pass Pint, and the tracked diff passes whitespace checks.

The source has no business stock/order rows, so populated SQLite fixtures and isolated MariaDB fixtures provide behavioral coverage. Do not interpret empty-source reconciliation as populated production acceptance.

| Check | Result |
| --- | --- |
| Backend suite | 47 tests / 580 assertions passed |
| React suite | 21 tests passed; one worker keeps the Windows test run stable |
| Vite production build | Passed |
| MariaDB separate-process final-unit checkout | Exactly one completed sale; competing request conflicts |
| Concurrent identical checkout | Both requests return the same sale; one payment/stock posting |
| Concurrent identical receipt | One receipt, stock movement and payable |
| Concurrent identical supplier return | One return and payable reduction |
| Sale vs supplier return on final unit | Exactly one consumes stock |
| Sale vs receipt | Receipt succeeds; checkout observes serialized stock availability |
| Inventory reconciliation after concurrent postings | Movement sum and reservations equal stored balances; no negative stock |
| MariaDB ledger immutability | Attempted movement edit rejected by trigger |
| Legacy opening fixture | Current cutover quantity imported once; original stock/price/margin unchanged; legacy mutation routes frozen |
| Source drift/mapping errors | Unreviewed quantity changes and identity/price changes block activation |
| Pricing/payment behavior | Tax/discount rounding, authoritative quote, expiry/reprice, partial/split/later payments, overpayment and walk-in credit rejection passed |
| Tenant/security behavior | Foreign reads/parents denied; role/approval restrictions; CSRF/session suite retained |
| Unit and stock behavior | Weighted precision, float rejection, non-stock services, stale counts, repeat approval/release and expired reservations passed |
| UI behavior | Scanner Enter, decimal payloads, validation errors, quote invalidation, rapid double click, identical network retry and activation confirmation passed |

Scripts retain fixture rows only in the isolated restore, never the source database. Private evidence: `pre-phase-4-5.json`, `phase-4-5-rehearsal.json`, `phase-4-5-concurrency.json`, `phase-4-5-apply-local.json`, and final read-only audit/backup reports. These files are not publication artifacts.

Actual browser/scanner/printer walkthrough, HTTPS session/cookie verification, physical opening-stock approval and production cutover remain pending. Shift close/cash movements and retail returns/refunds/reports are phase 6. Existing dependency security advisories remain a public-deployment gate; this change adds only the BCMath platform requirement and preserves the locked package versions.
