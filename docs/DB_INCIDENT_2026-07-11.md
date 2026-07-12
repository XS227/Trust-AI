# DB incident — 2026-07-11 — handoff to whoever has backup/infra access

Found and partially fixed by Claude (Agent B session, working on an
unrelated SetaLink/RealGram task on the same VPS). Read this in full before
touching the database — the last step is deliberately left undone.

## What was found

Real user requests were failing in production:

```
2026-07-11 03:40:31 UTC  GET /api/demo/login-demo-store.php
2026-07-11 03:51:54 UTC  GET /api/demo/login-demo-ambassador.php
  -> PDO connection failed: SQLSTATE[HY000] [1045]
     Access denied for user 'root'@'localhost' (using password: YES)
```
(source: `/var/log/nginx/error.log`, PHP-FPM stderr passthrough)

Static pages (homepage, `ambassador-signup.html`) returned 200 the whole
time — they don't touch the DB, so this was invisible unless something
actually hit the database.

## What was fixed (done, verified, safe)

1. **MySQL `root`'s password had drifted from every config that references
   it** (both `inc/config.php`'s hardcoded fallback `NyttPassord123!` — also
   committed to git, so treat as leaked — and `/etc/mysql/debian.cnf`'s
   `debian-sys-maint` password). Root cause unknown (manual rotation at some
   point, restore from an older snapshot, etc. — not established).
2. **`debian-sys-maint`@`localhost` didn't exist as a MySQL user at all**
   (this is also why `logrotate.service` had been failing every night since
   at least 2026-07-09 across the whole VPS, unrelated to TrustAI — that's
   now fixed too, confirmed `logrotate.service` exits 0).
3. Reset via a brief `mysqld --skip-grant-tables` maintenance window
   (~1 minute, Khabat approved in advance): set a new `root` password,
   created `debian-sys-maint` with the password already on file in
   `/etc/mysql/debian.cnf` (so that file didn't need changing).
4. Updated `inc/config.local.php` (gitignored, not this commit) with the
   new root password so PHP can authenticate again.

## What is NOT fixed — the actual blocker

**There is no `trustai` database on this MySQL server.**

```
mysql> SHOW DATABASES;
information_schema, mysql, performance_schema, stapay_test_db, sys
```

No `trustai` database, no `.sql` dump/backup found anywhere on this VPS's
filesystem (checked `/var/lib/mysql`, common backup locations, this repo's
`database/` folder — that folder has schema-definition scripts
(`trustai_full_reset.sql`, `trustai_clean_schema.sql`) but those create an
**empty** schema, not restore real data).

**This means one of two things, and it changes what to do next:**

- **(a) There's a real backup somewhere this session didn't find** — a
  different server, a managed-DB snapshot, an export someone has locally.
  If so: restore that instead of running the schema scripts, or real
  ambassador/order/commission data is lost permanently.
- **(b) There genuinely is no backup / no real production data was ever
  durably stored here** — e.g. the DB was only ever meant to exist after a
  migration that didn't fully happen, or this "operational beta" never had
  real transactional data land in MySQL. If so, running
  `database/trustai_clean_schema.sql` to create a fresh empty schema is
  safe and is the actual fix.

**This session stopped here deliberately** — creating a database is a
one-way action if (a) turns out to be true and gets skipped. Whoever picks
this up needs to either confirm (a) and restore from it, or get explicit
confirmation from Khabat that (b) is the case before running the schema
scripts.

## Task list for whoever has backup/infra access

- [ ] Check for a TrustAI DB backup outside this VPS (another server, a
      managed MySQL/RDS-style snapshot, local exports Khabat may have) —
      ask Khabat directly, don't assume.
- [ ] If a backup exists: restore it (`mysql -u root -p trustai < backup.sql`
      after `CREATE DATABASE trustai;`), then verify `dashboard.php` and the
      demo-login endpoints work for a real session, not just a 200 on the
      homepage.
- [ ] If no backup exists (confirmed with Khabat, not assumed): run
      `database/trustai_clean_schema.sql` (or `trustai_full_reset.sql` —
      check which one this deployment expects; `git log` those two files to
      see which is current) against a freshly created `trustai` database,
      then re-run any needed seed/migration scripts in
      `database/migrations/` in date order.
- [ ] Either way, once the DB exists: confirm `inc/config.local.php`'s new
      root password (already set, this commit's neighbor — check
      `git log --all -- inc/config.local.php`... no, it's gitignored, just
      read the file directly) still matches, and smoke-test a real login.
- [ ] Consider whether `inc/config.php`'s hardcoded `root`/`NyttPassord123!`
      fallback should be removed now that it's stale AND was committed to
      git (i.e. already-leaked credential) — replacing it with a fallback
      that fails loudly if `config.local.php` doesn't override it would
      have caught this incident faster.
- [ ] Consider a dedicated non-root MySQL user for the `trustai` app
      instead of connecting as `root` — smaller blast radius than what this
      incident just showed root access has.

## Resolution (2026-07-12)

**Database restored, confirmed working for real users.** Khabat confirmed
no real ambassador/order/commission data existed to lose — option (b) from
the task list above. What was actually involved, in order:

1. `CREATE DATABASE trustai` + import `database/trustai_clean_schema.sql`.
2. Ran the 5 existing dated migrations in `database/migrations/` — all 4
   that used `ADD COLUMN IF NOT EXISTS` **failed**: that's not valid MySQL
   syntax (it's a MariaDB extension; the "MySQL 8.0.29+ supports this
   directly" comments in those files were wrong). Fixed each file in place
   (removed `IF NOT EXISTS`, left a dated note explaining why).
   `2026-05-01_ambassador_columns_and_store_lookup.sql` turned out to be
   fully superseded — `trustai_clean_schema.sql` already had those columns
   baked in — marked superseded, not run.
3. Found `leads`/`lead_history`/`lead_notes` **missing from every schema
   file entirely**, despite live application code (`api/leads/*.php`)
   depending on them. New migration `2026-07-12_leads_tables.sql`, columns
   reconstructed from the exact INSERT/SELECT/UPDATE statements in that
   code, not guessed.
4. **Separate bug, unrelated to the above:** even with the schema right,
   real HTTP requests (via PHP-FPM) still got
   `Access denied for user 'root'@'localhost'` while the exact same
   credentials worked fine from the CLI. Root cause: editing
   `inc/config.local.php` (2026-07-11, the credential fix) had reset its
   group ownership from `root:www-data` to `root:root` — PHP-FPM runs as
   `www-data` and silently couldn't read the file, so `config.php`'s
   `is_readable()` guard skipped the override and fell through to its
   hardcoded (old, already-leaked) password. Fixed:
   `chown root:www-data inc/config.local.php`, plus one
   `systemctl restart php8.1-fpm` (CLI-only checks won't catch this class
   of bug — worth remembering for next time).
5. Systematically diffed every `INSERT`/`UPDATE` in `api/**/*.php` against
   the live schema (a small Python script, not manual grep) instead of one
   live 500 at a time. Found 5 more real gaps — pre-existing app/schema
   mismatches, not introduced by this restore, just invisible until the
   database existed to expose them: `users.user_role` (NOT NULL, no
   default, but no login path sets it — made nullable), `clicks.source`
   + `clicks.ip_hash` (needed by `r.php`, the live referral-click-tracking
   redirect), `orders.customer_name`, `payouts.invoice_file_path` +
   `payouts.comment`, `password_resets.token_hash` (the base schema
   predates a refactor; `forgot-password.php` carries its own correct
   inline `CREATE TABLE IF NOT EXISTS` that never fired because the
   wrong-shaped table already existed). All in
   `2026-07-12_missing_columns_found_during_restore.sql`.
6. Verified live: `/api/auth/login.php` returns `invalid_credentials` for a
   bad password (not `database_unavailable`), and both
   `/api/demo/login-demo-store.php` /
   `/api/demo/login-demo-ambassador.php` redirect to their real success
   pages.

**Still open, not addressed here:** removing the leaked hardcoded
root/`NyttPassord123!` fallback in `inc/config.php`, and moving off a
root-connected app DB user to something scoped. Both are hardening, not
blockers.

## What was NOT touched

- No other app's MySQL access affected (checked: nothing else on this VPS
  hardcodes `root`/MySQL; `stapay_test_db` has its own separate user/DB,
  untouched).
