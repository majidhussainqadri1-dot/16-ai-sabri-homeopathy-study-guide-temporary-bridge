# Privacy and Data Lifecycle

## Stored

Owner/guest-bound session metadata; encrypted questions/answers; citations; safety category; feedback; irreversible request hash; token/cost telemetry; retention date; review/audit/outbox metadata. AI Teacher drafts are encrypted.

## Excluded

Private clinical records, private messages, identity evidence, private Saved Studies and unrestricted user data are not automatic corpus/provider inputs. Raw prompts and answers are never written to audit/metric logs.

## External providers

Only the redacted prompt and approved bounded source chunks are sent when an administrator enables a provider. The Custom GPT bridge opens externally and receives no native private session/corpus access.

## Rights

WordPress export and erasure integrations, direct session JSON export/delete, provider-deletion hook, configurable retention and a narrowly authorized legal/security hold. Held records are reported honestly, excluded from ordinary use and erased after hold release.
