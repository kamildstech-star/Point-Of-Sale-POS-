# Phase 6–7 local verification

Date: 2026-10-20. Environment: PHP 8.2.12, Laravel 11.8.0, MariaDB 10.4.32, React JavaScript/Vite. Production deployment and real Stripe account tests are not claimed.

| Check | Result |
| --- | --- |
| Before-change backup and restore | `backup_828619fc1a0a4fde8a5404896a58a954` restored into isolated `bu_restore_78d59dfea2d5efbd` |
| Additive migration rehearsal | Both Phase 6–7 migrations and advanced installer succeeded; original columns/counts and environment unchanged |
| MariaDB separate-process races | Return versus return; identical/different refund requests; cancel versus return; shift close versus refund; simultaneous product creates at plan limit; all passed |
| Stock and financial reconciliation | Passed after the concurrent workloads |
| Local source application | Succeeded; original field/count fingerprints and `.env` preserved; no automatic branch activation, Stripe subscription or charge |
| Source schema audit | 107 tables, 51 applied migrations; completed read-only |
| Backend | `php artisan test --compact --do-not-cache-result --log-junit=../docs/audit/private/phase-6-7-tests.xml`: 67 passed, 1,011 assertions |
| React | `npm.cmd test -- --reporter=dot`: 25 passed |
| Build | `npm.cmd run build`: passed; 115 modules, approximately 324 kB JS before gzip |
| Style | Laravel Pint applied to related files; new migrations retain their rehearsed behavior |
| Post-installation encrypted backup/restore | `operations:backup --restore-drill` succeeded: `backup_2903e437e56e4907a01226efe3d5ebc3` → isolated `bu_restore_44134a6efbeaefd1`; database reconciled and 2,948 backend files verified |
| Security review | Completed with unresolved findings: 59 backend advisory entries across 16 packages; 5 frontend entries (2 critical/3 moderate); compatible npm audit-fix dry run proposed no changes |

Added tests cover original-value tax/discount allocation across split returns, damaged goods, credit-before-refund, overreturn/overrefund prevention, double cancellation/restock, atomic exchanges, cash/expenses/closing, hosted Checkout/idempotency, live-key rejection, owner permission, client tenant-ID isolation, raw signature verification, duplicate/out-of-order events, retry, activation, all relevant access statuses, grace, cancellation, mapped upgrades/downgrades, product/branch limits, legacy access, old GET mutation/write gates, CSV privacy/expiry/import idempotency, receipt settings, disabled alert templates and preserved legacy ledger namespaces.

Private raw evidence remains in `docs/audit/private`, excluded from Git. Encrypted backups include backend application/config/private uploads, not the separate frontend release artifact. The local DPAPI recovery key is tied to this Windows user. Use an approved off-host retention/key recovery process for production.

The original dependency advisories, missing legacy controller/route-reflection issues recorded in earlier audits, populated historical accounting review, browser/scanner/printer UAT, staging HTTPS, real Stripe test keys/Price IDs and full Checkout/Portal/renewal lifecycle verification remain open deployment gates. Provisional prices/limits require approval; live charging remains disabled by default. Phase 8 historical cutover has not begun. See [implementation](../PHASE_6_7.md) and [Stripe setup](../STRIPE_BILLING.md).
