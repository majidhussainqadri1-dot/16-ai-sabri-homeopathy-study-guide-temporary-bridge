# Architecture

File 16 is the canonical owner of governed educational AI behavior, not of source content, identity, publication, global search, notifications or the application shell.

## Request path

1. REST authenticates a WordPress nonce or guest HMAC request token.
2. Current File 00 claims are re-read and fail closed for unavailable/suspended/revoked/risk/guardian/consent state.
3. Session ownership and assistant-mode eligibility are checked.
4. Prompt policy and PII redaction run; encrypted original and redacted forms are stored.
5. Retrieval queries only approved accessible chunks from canonical owner sources.
6. Selected provider receives only redacted text and bounded sources.
7. Output clinical/privacy/security policy runs, then citation validation.
8. Only a passing/withheld/refusal result is encrypted and stored; usage stores a digest, not prompt text.
9. Audit and transactional outbox events are emitted without private content.

## AI Teacher path

The scheduler creates four unique daily slot rows. A worker claims due rows, retrieves approved sources, calls the configured provider, validates output/citations and stores an encrypted draft. At least 30 launch days require human review. Publication is a versioned handoff to File 21; File 22 receives review-ready drafts. File 16 never creates a duplicate content post.

## Canonical boundaries

- File 00: identity, current claims and account eligibility.
- Files 05/06/12/15/research: source truth.
- File 19: notification delivery.
- File 20: shared Back/Home and shell placement.
- Files 21/22: publication/review truth.
- File 23: creator/founder dashboard projection.
- File 24: assurance/incident evidence.
- File 25: global visual tokens.
- File 26: search/discovery/ranking owner.
