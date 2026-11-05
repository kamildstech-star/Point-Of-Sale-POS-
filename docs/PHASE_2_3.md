# Tenant foundation and universal catalog

> Phases 4–5 are now implemented and applied locally; read [PHASE_4_5.md](PHASE_4_5.md). References below to future inventory/customer/checkout work describe the phase 2–3 baseline. Original legacy rows remain preserved, and no branch was activated automatically.

Implemented locally on 2026-10-19 against MariaDB 10.4.32. The three new migrations have been applied after a local restore rehearsal. The local database now has one Best Uniform tenant, memberships for the two existing staff accounts, a MAIN branch/location, scoped roles and catalog tables. No original stock quantities, financial amounts, user credentials or historical records were changed. The original `.env` checksum is unchanged.

Start the backend and frontend using [IMPLEMENTATION.md](IMPLEMENTATION.md). Sign in with an existing staff account; a single membership is selected automatically. Use Businesses to create or switch businesses, Team & roles to manage staff, Catalog setup to define units/categories/brands/tax/pricing, Products to manage products/variants/images, and Legacy mapping to review source conversion. New owners can register at `/register`. A new tenant starts without legacy catalog or stock data.

## Schema and isolation

Legacy `products`, `sizes`, `schools`, `stocks`, orders, returns, dealer/accounting tables, settings, logs and tasks are retained. Seventeen legacy business tables received `tenant_id`, were assigned to the legacy tenant and reconciled, then had ownership enforced. Their database defaults keep future Blade inserts owned by Best Uniform regardless of the React business selection. Shared `users`, legacy `roles`/`permission`, sessions/cache/jobs are intentionally not tenant-owned. Retail customers remain in the shared legacy users table until phase 5; they cannot authenticate without a verified staff membership.

The new schema uses `tenant_roles`, `tenant_permissions`, `tenant_role_permissions`, `tenant_memberships`, `branches`, `locations`, `audit_logs` and `tenants`. Memberships currently have one tenant-scoped role; branch assignments and multiple simultaneous membership roles are future extensions. Permission codes are stable strings. System roles are immutable; authorized managers can create/edit custom roles only with permissions they themselves hold. Only an owner may assign/remove owner access, and the last active owner cannot be demoted or suspended.

Universal data uses `catalog_products`, `catalog_variants`, `catalog_categories`, `catalog_brands`, `catalog_units`, `catalog_attributes`, `catalog_attribute_values`, `catalog_variant_values`, `catalog_barcodes`, `catalog_images`, `catalog_price_lists`, `catalog_price_items`, `catalog_tax_classes`, `catalog_tax_rates`. These names avoid collisions with the Blade schema. New composite foreign keys enforce same-tenant parent relationships. Every product gets at least one variant. Services/digital products do not track physical inventory. Money uses DECIMAL(19,4); prices are validated as decimal strings. Tax records are configuration, not a newly implemented checkout tax calculation. Overlapping rates in the same class/jurisdiction are rejected.

Tenant context comes from a server-side session after an active-membership check. Request-body tenant IDs never authorize access. Reads and mutations use tenant-scoped queries and a permission policy; no legacy super-admin bypass exists in the new APIs. Detail/update/archive and file routes resolve records inside the current tenant. Suspended tenants retain authorized reads/export while mutations are blocked. Switching rotates session/CSRF and remounts the tenant UI, clearing prior component state. Memberships are rechecked on subsequent requests.

`TenantContext::cacheKey()` and `storagePath()` namespace infrastructure data. `TenantJobMiddleware` carries explicit tenant/actor IDs, revalidates membership/status when a queued job executes, and restores prior context in `finally`. Future tenant jobs must use it; stock jobs do not exist yet. Product uploads use the private `tenant_private` disk and authorized image IDs, not public storage URLs. JPEG/PNG/WebP uploads are bounded to 2 MB. CSV export is scoped and protects against spreadsheet formula injection. The API never returns shared password hashes or remember tokens. Existing staff identities can be granted another membership without changing their shared profile/password; legacy customer identities require reviewed separation rather than automatic conversion.

## Approved legacy mapping

The business owner explicitly approved **distinct SKUs per school/product/size stock row** in this session. This approval is recorded in the legacy tenant and audit log. Generated SKUs are `LEG-S{school}-P{product}-Z{size}-K{stock}`; the stock ID prevents accidental merges. Each mapped stock row has its own new catalog product/variant in this first migration. Grouping catalog headers can be considered later without merging SKU identity.

`legacy_record_mappings` records each source stock ID, target variant, target location, batch, checksum and complete product/school/size/stock snapshots. Source names, original image paths, school margin, raw enum values and prices are preserved without translating unknown enum/tax meanings. Source image paths are evidence; automatic public-file copying is deliberately not performed. New private uploads are available in the product screen. Stock cost/sale prices are defaults; school-margin pricing semantics need a later reviewed pricing policy.

`migration_exceptions` queues unapproved policy, missing references, mismatched product-specific sizes, duplicate stock combinations, invalid prices/negative quantities, barcode/SKU collisions, oversized labels and source/location changes after mapping. No ambiguous row is merged or silently discarded. Correct source issues through the legacy system and retry. A changed established mapping requires a reviewed decision rather than rewriting its evidence. `migration_batches` records totals; each current source row is counted as mapped or unresolved. Repeated execution does not duplicate products, variants or mappings.

The local source contains **zero stock rows**, so the local reconciliation is total 0 / mapped 0 / unresolved 0. Populated isolated test fixtures verify distinct school mappings, original labels/images/enums, prices, duplicate combinations, barcode collisions, source changes and repeat execution. This is not evidence of populated production reconciliation. MAIN is an initial location placeholder; confirm physical locations and currency before phase 4. No opening balance, movement ledger, historical sale replay or stock posting was performed.

## Applying on another local copy

Use a verified encrypted backup and isolated restore first; see [RECOVERY.md](RECOVERY.md). The provided helper is restricted to loopback MySQL/MariaDB and never edits `.env`:

```powershell
# Repository root, after restoring a backup with legacy-recovery.ps1:
php scripts/apply-phase-2-3.php rehearsal backup_REPLACE_WITH_32_HEX_ID
php scripts/apply-phase-2-3.php apply-local
```

The helper validates its target connection, applies the first two migrations, runs the idempotent ownership backfill, verifies/seals legacy ownership, records the approved distinct-SKU policy and reconciles catalog mappings. It fingerprints every original legacy field excluding only the newly added ownership column. Private verification reports are under ignored `docs/audit/private`. Currency for the initial legacy tenant is PKR and time zone is Asia/Karachi; review these against the target business before using the helper elsewhere. The command explicitly records the already approved distinct-SKU decision; do not reuse that approval for a different business without review.

For manual staged execution, migrate `2026_10_19_100000_create_tenant_foundation.php` and `2026_10_19_110000_create_universal_catalog.php`, run `php artisan legacy:bootstrap` to inspect its plan, then `legacy:bootstrap --apply`. Reconcile ownership before migrating `2026_10_19_120000_enforce_legacy_tenant_ownership.php`. Finally use `legacy:map-catalog --owner=<verified owner ID> --location=<verified location ID> --approve-distinct` only with owner approval. Do not run legacy seeders or destructive migration commands.

A rehearsal initially revealed that copying a resolved Laravel connection config retains its original internal connection name, and schema facades may cache a connection. That caused empty new tables to be created locally before the rehearsal completed. No original business fields changed. The helper now gives each connection its own name, binds the schema builder to that same verified connection, and passes the connection explicitly to migrations. Existing empty partial tables were compared against successful rehearsal columns/indexes/FKs before being retained and completing their migration records. Retained failed rehearsal databases contain no production writes; no database was dropped. The source schema and financial preservation checks now pass.

## Verification and rollback

Final local checks: **32 backend tests / 275 assertions**, **15 frontend tests**, and a successful Vite production build. The database contains **53 tables and 48 applied migrations**. See [the verification record](audit/phase-2-3-verification.md) for evidence and limitations. Custom role manage permissions require their corresponding read permissions; location administration also requires catalog read access for its lookups.

Run backend tests with `php artisan test --no-ansi --do-not-cache-result`, frontend tests with `npm.cmd test`, and the bundle with `npm.cmd run build`. Tests use isolated SQLite fixtures rather than running the unsafe historical migration chain; the actual new migrations and ownership backfill were also rehearsed on MariaDB. No browser-controlled HTTPS UAT is claimed. A public staging server is still unavailable, and production backup reconciliation and dependency-security upgrades remain outstanding.

Application rollback keeps the expanded schema, source ownership and migration evidence. Route users back to the existing Blade deployment and compatible backend version; retain the established application key and encrypted backup. These migrations intentionally refuse destructive `down()` operations. Do not restore the old DB over current operations or remove tenant columns, memberships, catalog rows or historical mappings as an application rollback.
