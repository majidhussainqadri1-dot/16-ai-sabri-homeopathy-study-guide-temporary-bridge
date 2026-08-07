# Data Schema 2.1.0

- `scha_sessions`: owner/guest binding, plan/role/claims snapshot, assistant mode, provider/model, status, legal hold, retention and optimistic version.
- `scha_messages`: encrypted content/redacted content, role, citations, safety/provider/idempotency metadata.
- `scha_corpus_items`, `scha_chunks`: canonical owner/version/licence/approved-use/rights-evidence/access/provenance and retrieval text; approval is fail-closed.
- `scha_usage`: irreversible request digest, provider/model/tokens/cost and idempotency.
- `scha_feedback`, `scha_policy_versions`, `scha_evaluation_runs`.
- `scha_audit_log`: scrubbed action evidence.
- `scha_outbox`: transactional integration events with retry/dead letter.
- `scha_teacher_posts`: four-slot schedule, encrypted draft, citations, review, atomic publication claim, canonical idempotency key/pointer, retry/error state.

No File 00 identity record, File 21 post, File 19 notification or File 26 search index is duplicated here.
