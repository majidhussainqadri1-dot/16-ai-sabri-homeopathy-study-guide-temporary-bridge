# Security

- Object ownership is checked on every private session operation.
- Guest sessions are bound to an HttpOnly SameSite token stored only as a hash.
- REST writes require nonce validation; authorization is not inferred from route availability.
- External endpoints require HTTPS, an explicit hostname allowlist and public-IP resolution.
- Prompt injection and secret/private-data extraction requests are refused.
- Raw secrets, API keys and private operational playbooks are excluded from code, logs and audit context.
- Rate limits, quotas, idempotency and cost budgets limit abuse and duplicate charges.
- Private routes are no-cache and noindex.
