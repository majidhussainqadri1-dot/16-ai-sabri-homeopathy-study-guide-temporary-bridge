# Security Controls

- Fail-closed live File 00 account claim checks at protected actions.
- Owner-scoped session lookup with 404 anti-enumeration and 410 unavailable state.
- Logged-in REST nonce; guests require an HttpOnly SameSite binding plus hourly HMAC request token.
- Authenticated encryption at rest for questions, answers and AI Teacher drafts; key derivation uses WordPress salts and purpose separation.
- Provider secrets accepted only from server constants/environment; no browser/repository secret.
- Fixed Claude endpoint; JSON provider requires HTTPS, allowlisted DNS host and SSRF-safe validation.
- Input prompt-injection/private-data/clinical filters and post-generation output safety gate.
- Citations fail closed; insufficient evidence produces no fabricated answer.
- Rate/fair-use limits, provider budgets, retries, idempotency and transactional outbox.
- Audit context excludes raw prompts, answers, provider keys and patient data.
- Private routes use no-store, noindex/noarchive, strict referrer and restricted permissions headers.

External penetration, WAF, hosting, dependency, database privilege and restore evidence remain File 24/staging gates.
