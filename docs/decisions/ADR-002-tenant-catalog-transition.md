# ADR-002: Add tenant and catalog namespaces beside Blade

Date: 2026-10-19. Status: implemented locally; production rollout pending.

Preserve all legacy table names and models. Use `tenant_*` for new role/membership authorization and `catalog_*` for universal products and supporting metadata. Backfill seventeen legacy business tables to the one Best Uniform tenant, then enforce ownership/defaults so old Blade inserts remain owned by that business. Shared identities and legacy role lookups remain global until the planned customer/auth split.

Use one scoped role per membership initially. Composite tenant/parent foreign keys plus scoped API queries prevent cross-business relationships. Retain MariaDB 10.4.32 for this local implementation; this does not select or certify a production engine/version. Future production migration requires the populated backup/staging gate in ADR-001.

The business owner approved distinct school/product/size SKUs on 2026-10-19. No shared SKU merges are authorized. Preserve complete raw snapshots and source IDs; send collisions/missing references to an explicit exception queue. Catalog mapping does not post opening inventory. Original images and enums are retained as evidence without inferred business meanings.

Retain added schema during rollback. No financial or mapping rows cascade-delete, and automated down migrations are disabled. The deployment helper verifies connection names/schema bindings and compares any empty partial DDL against a successful rehearsal before adopting it.
