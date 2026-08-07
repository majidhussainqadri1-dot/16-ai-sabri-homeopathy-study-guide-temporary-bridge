# File 16 Requirements Traceability — Release 2.2.0

| Requirement | Implementation evidence | Verification |
|---|---|---|
| F16-FR-001 | `SCHA_Entitlements`, `SCHA_Account_Context`; latest directive resolves historical separate entitlement to one free tier | four-plan negative-regression test |
| F16-FR-002 | `SCHA_Session_Service`; owner/guest binding, mode, locale, provider, retention, export/delete | unit/static + staging journey |
| F16-FR-003 | `SCHA_Prompt_Policy`, `SCHA_Privacy`, length/rate/PII warnings | adversarial suite |
| F16-FR-004 | `SCHA_Corpus` canonical owner allowlist, version/access/licence/approved-use/rights-evidence/language/checksum registry | package/static + staging rights review |
| F16-FR-005 | `SCHA_Corpus::approve_and_index`, chunks and provenance | contract/static + staging indexing |
| F16-FR-006 | `SCHA_Retrieval`, access-class filter, lexical rerank, bounded/diverse results | static + integration tests |
| F16-FR-007 | `SCHA_Citation_Validator`, canonical source/version/location/url | unit/static |
| F16-FR-008 | provider prompt + output/citation withholding and insufficient-evidence stop | adversarial/static |
| F16-FR-009 | `SCHA_Prompt_Policy`, `SCHA_Output_Policy`, escalation links | adversarial suite |
| F16-FR-010 | provider registry: local, Claude, bridge, allowlisted HTTPS JSON | static + staging provider tests |
| F16-FR-011 | `SCHA_Usage_Ledger`, monthly and AI Teacher daily safety budgets | static + staging telemetry |
| F16-FR-012 | `SCHA_Rate_Limiter`, fair-use limits, server-derived guest identity | static + load test gate |
| F16-FR-013 | `SCHA_Privacy`, `SCHA_Crypto`, guest HMAC, external-provider redaction | unit/static/security review |
| F16-FR-014 | retention cron with post-expiry pseudonymization, export/erase without legal-hold pagination loops, provider deletion hook, non-bypassable legal hold | static + staging lifecycle |
| F16-FR-015 | `SCHA_Feedback`, categories and escalation outbox | static + staging workflow |
| F16-FR-016 | `SCHA_Evaluation`, multilingual input and output/privacy gates | unit/adversarial + release evaluation |
| F16-FR-017 | safe refusal links, File 22 review handoff, owner publishing workflow | static + companion integration |
| F16-FR-018 | strict Custom GPT URL bridge and external disclosure | static + browser gate |
| F16-FR-019 | health, audit, metrics, transactional outbox, dead-letter operations | static + staging operations |

| Requirement | Implementation evidence | Verification |
|---|---|---|
| F16-NFR-01 | current File 00 claims, ownership checks, guest HMAC token, 404 anti-IDOR | security/static + staging roles |
| F16-NFR-02 | encrypted session content, redaction, export/erase, legal hold, audit scrubbing | crypto unit + privacy lifecycle |
| F16-NFR-03 | idempotency, provider failure withholding, outbox retry/dead letter, atomic AI Teacher publication claim/retry | unit/static + chaos gate |
| F16-NFR-04 | bounded retrieval, limits, indexed columns, async teacher/outbox | static + load gate |
| F16-NFR-05 | keyboard, labels, live regions, logical RTL, 44px, reduced motion/data, accessibility page | static + browser/AT gate |
| F16-NFR-06 | trace IDs, safe audit, health, metrics, provider/corpus/queue status | static + monitoring gate |
| F16-NFR-07 | schema lock/dbDelta, non-destructive uninstall, migration/rollback plan | package/static + restore gate |
| F16-NFR-08 | admin health, corpus, evaluation and AI Teacher review/queue operations | static + operator journey |
| F16-NFR-09 | plugin header requires WordPress 7.0 and PHP 8.3; lint on PHP 8.3 | CI/local lint |
| F16-NFR-10 | translatable strings, locale snapshot, RTL logical CSS and localized JS | static + locale browser gate |

## Cross-plan amendments

- **One free tier/no donor advantage:** `SCHA_Entitlements`, public/admin copy and module/search manifests.
- **AI Teacher / Claude / four daily posts:** `SCHA_AI_Teacher`, Claude provider, scheduler, admin review and File 21/22 contracts.
- **Top-20 AI capability families:** `SCHA_Assistant_Modes`, File 26 explainable projection, accessibility/low-bandwidth controls.
- **Canonical ownership:** no duplicate publication, search, notification, identity or shell backend.
