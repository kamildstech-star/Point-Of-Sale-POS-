# Backup, restore rehearsal and rollback

The local helper is restricted to a loopback MySQL/MariaDB host. It reads configuration through Laravel, never prints credentials, and never changes `.env` or `APP_KEY`. It does not invoke migrations or seeders. Use a quiet local copy so data does not change between audit and dump. For a busy production database use a coordinated database/files snapshot and restricted backup credentials, then transfer encrypted artifacts to a separate staging server.

Run from the repository root on this Windows/XAMPP installation:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/legacy-recovery.ps1 -Action backup
# Use the backup_<32 hex characters> ID printed by the command:
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/legacy-recovery.ps1 -Action restore -BackupId backup_REPLACE_WITH_ID
```

Backups are stored under ignored `docs/audit/private/<backup-id>/`. Database SQL and the application/config archive use authenticated libsodium secretstream encryption. A random key is protected with Windows DPAPI for the current Windows user in `key.dpapi`; no secret appears in shell arguments or logs. This local rehearsal key is tied to the user profile/machine. For disaster recovery, escrow an independently recoverable encryption key through the organization's secret manager and store encrypted backups off the host. Copying `key.dpapi` to another account is insufficient.

The application archive includes `.env`, source, assets and actual uploaded files under the backend. It skips dependencies, generated caches, logs and symbolic links. Existing broken `public/storage` links are not followed; actual files under `storage/app` are included. External object storage and web-server configuration outside this workspace require a separate backup. Do not treat this local archive as a complete production deployment backup.

Restore decrypts SQL into a temporary private file, creates a fresh randomly named `bu_restore_<hex>` database on the local server, imports only there, and compares table metadata/DDL/counts, business-row SHA-256 fingerprints, stock-by-ID balances, financial sums and integrity findings. It also authenticates the files archive and verifies every file against its manifest. Plaintext temporary artifacts are deleted when the operation exits normally or throws. An interrupted OS process may require operator cleanup of its exact temporary files. Restore databases and protected artifacts are retained for inspection; no database is automatically dropped.

Never point a restore at the source database, run `migrate:fresh`/`refresh`, regenerate the established key, or run legacy seeders against existing data. Verify backup decryption and restore before production migration approval. The current local database has no retail history; repeat the full rehearsal with the populated production snapshot. A local empty-data restore cannot validate historical accounting semantics.

Before a production rehearsal, inventory database users/grants, scheduled events, triggers, routines, storage engines, file stores and external integration credentials. Disable email, queues, payment callbacks and scheduled side effects in isolated staging. Record the backup timestamp, checksums, server/version, restore target, reconciliation differences and operator sign-off without committing customer information.

Phase 1 rollback: route frontend traffic back to Blade; stop the React deployment; revert only the foundation API/config/UI changes and restore the prior Composer manifests/dependencies. No business migration or data rollback is required. Preserve session security fixes where possible. Keep the existing application key and database. After phase 2 starts, each additive migration needs a separate reviewed rollback plan.
