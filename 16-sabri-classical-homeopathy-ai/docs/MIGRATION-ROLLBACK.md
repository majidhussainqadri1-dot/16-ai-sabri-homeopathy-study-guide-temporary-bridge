# Migration and Rollback

Activation applies idempotent schema version 1.1.0 through `dbDelta`, seeds the versioned safety policy and installs schedules/capabilities. Upgrades use a transient migration lock.

Before deployment: create and verify a restorable database/files backup, record the current plugin version and schema, test fresh activation and upgrade on staging, and export configuration without secrets.

Rollback: disable File 16 routes, switch provider to local or disable the plugin, restore the prior plugin package, and restore the database snapshot when a schema rollback is required. Uninstall is non-destructive by default; destructive purge requires explicit `SCHA_PURGE_ON_UNINSTALL` authorization.
