# Migration and Rollback — 1.x to 2.1.0

## Preflight

- Export database and files; prove isolated restore.
- Record exact plugin/version/schema, row counts and checksums.
- Confirm WordPress 7.0+, PHP 8.3+, File 00 claims contract and canonical companion owners.
- Configure provider secrets outside repository and import only approved corpus sources.

## Upgrade

`dbDelta` under an activation/upgrade lock adds assistant mode, claims version, legal hold, encryption version and AI Teacher queue fields/table. Existing plaintext messages remain readable but new writes are encrypted; a controlled background migration may re-encrypt legacy content after backup/verification. Old paid-entitlement user meta is ignored by runtime.

## Rollback

Disable provider/AI Teacher kill switches, stop cron, preserve the database, restore the pre-upgrade files/database together, clear caches/rewrite rules and reconcile outbox/search projections. Do not downgrade after irreversible legacy-message re-encryption unless the old version can read the `scha2` envelope. Non-destructive uninstall is default; purge requires both constant and option.

The historical GitHub repository name remains a compatibility alias. Canonical package folder/slug are already correct; any repository rename requires an explicit external change record and redirects.
