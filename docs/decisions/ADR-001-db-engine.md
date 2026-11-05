# ADR-001: Preserve the current engine during the API transition

Date: 2026-10-19. Status: accepted for local phases 0–1; production engine decision pending.

The configured local connection uses Laravel's `mysql` driver against **MariaDB 10.4.32**. The read-only audit found 28 tables including `migrations`, with all 45 repository migrations applied. This is observed runtime evidence, replacing the earlier assumption that SQLite might be in use. The database contains setup records and no retail transactions.

Keep this connection and schema for the initial session/API shell. Do not convert engines, rewrite historical migrations, re-seed, regenerate the application key, or apply the proposed SaaS schema during phase 1. Isolated PHPUnit tests use SQLite in memory with explicit minimal legacy fixtures. These tests establish authentication behavior, not MySQL concurrency or financial correctness.

The blueprint proposes MySQL 8+ or PostgreSQL for the eventual SaaS. Neither has been selected for production by this change. Before phase 2 database work, inspect the actual production server, verify a populated encrypted backup on a separate staging server, select and document the supported production engine/version, and test additive migrations there. MariaDB and MySQL are distinct products; compatibility must be verified rather than assumed.
