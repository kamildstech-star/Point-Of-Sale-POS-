# Phase 2–3 local verification

Date: 2026-10-19. Environment: local XAMPP / PHP 8.2.12 / Laravel 11.8.0 / MariaDB 10.4.32. Production and hosted HTTPS staging remain unverified.

| Check | Result |
| --- | --- |
| New migrations | 3 applied; all 48 total migrations recorded as ran |
| Observed tables | 53, including 25 new tenancy/catalog/mapping tables |
| Legacy ownership | 17 existing business tables backfilled and constrained; original non-ownership fields/counts unchanged |
| Initial business | One Best Uniform tenant, two existing staff memberships, one MAIN branch/location |
| Owner SKU decision | Distinct SKUs per school/product/size stock row; explicit approval recorded |
| Local mapping reconciliation | 0 total / 0 mapped / 0 unresolved; local stock table is empty |
| Backend tests | 32 passed, 275 assertions, isolated SQLite fixtures |
| Frontend tests | 15 passed, React Testing Library/Vitest |
| Frontend production build | Passed; JavaScript/JSX only |
| MariaDB rehearsal | Additive migrations, ownership constraints/backfill and mapping completed on isolated encrypted-backup restore |
| Post-migration backup | Encrypted expanded database/application backup restored successfully; schema/counts/business fingerprints and 2,877 archived files verified |
| Original business fields | Before/after fingerprints excluding the new tenant column match |
| Existing `.env` and application key | Preserved; original file checksum unchanged |
| New tenant isolation | A/B list/detail/update/archive/export/file access, FK submissions, switching, revocation and suspension tested |
| Mapping fixture coverage | Distinct school SKUs, source snapshots/images/enums, repeat execution, duplicate combinations, barcode collisions, changed source |
| Historical stock/order posting | None performed; opening balances remain phase 4 |
| Legacy route issues | Pre-existing missing controller/method references remain; full route:list still encounters those failures |

Private evidence, encrypted backups, protected recovery keys and exact fingerprints are ignored under `docs/audit/private/`. No customer or credential records are included in this document. The original phase 0 schema report remains the pre-change baseline; current schema is defined by the three new migrations and the private post-phase audit.

A connection-selection bug during the initial rehearsal created empty new tables locally before migration registration completed. No original business fields changed. Resolved configs now carry distinct connection names, the schema builder is rebound to the verified target, and migrations receive an explicit connection. The empty local tables were compared against the successful rehearsal's columns/indexes/FKs, retained and fully registered. No legacy tables or records were deleted to recover from the interruption.
