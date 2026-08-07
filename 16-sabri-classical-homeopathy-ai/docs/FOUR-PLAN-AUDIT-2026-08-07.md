# File 16 — Four-Plan Audit and Corrective Register

**Date:** 2026-08-07 (Pakistan Standard Time)  
**Corrective release:** 2.1.0 / schema 2.1.0  
**Canonical owner:** File 16 — source-linked educational AI, provider/model abstraction, approved-corpus retrieval, citations, policy/refusal, usage/cost safeguards, feedback/evaluation, privacy/audit, temporary bridge and institutional AI Teacher generation governance.

## Governing corpus and precedence

1. **SSH-PMP-2026-v3.0** — product constitution, canonical ownership, verified-entry principle, status truth, security/privacy, release gates and numbered modules.
2. **Sabri Recovered Directives v2.1** — latest Founder amendments: central green, RTL/right-priority, one complete free tier, no donor advantage, verified protected actions, AI Teacher powered by Claude, four educational drafts daily and continuing review/fix doctrine.
3. **Continuous Value / Global Top-20 Superset v1.0** — one-roof utility, transparent/explainable AI, learning/search/creator/doctor-administrative assistance, healthy-use, low-bandwidth, accessibility, provider abstraction, evaluation and File 26 discovery ownership.
4. **SSH-F16-PLAN-2026-v1.0** — 19 FRs, 10 NFRs, source-linked sessions, separate historical entitlement requirement, corpus/retrieval/citations, provider governance, safety, privacy, evaluation, migration, rollback and operations.

**Precedence applied:** definitive Islamic/safety constraints and latest explicit Founder directive → later approved recovered directive → dedicated File 16 requirement → verified implementation evidence. Therefore the older paid/add-on interpretation is superseded by the later one-complete-free-tier rule, while fair-use and provider-cost controls remain noncommercial safeguards.

## Review round 1 — Constitution, scope, ownership and business-law review

### Defects found

| ID | Severity | Defect |
|---|---:|---|
| R1-D01 | Critical | Runtime still treated AI as a separately paid `ai-addon` and exposed user-level paid entitlement administration, conflicting with the latest one-complete-free-tier directive. |
| R1-D02 | High | No executable four-plan precedence/trace manifest existed. |
| R1-D03 | High | File 16 had no governed institutional AI Teacher, despite the recovered central directive and Top-20 AI Teacher capability. |
| R1-D04 | Medium | Cross-file ownership did not explicitly state that File 21/22 own publication, File 20 owns shared context controls, File 26 owns search/discovery, and donations cannot bias AI. |
| R1-D05 | Medium | Release identity and runtime baseline remained 1.0.0, WordPress 6.6 and PHP 8.1 rather than the current project baseline.

### Corrections applied

- Replaced paid/add-on entitlement state with `single-free-tier`; guest demo remains optional and tightly bounded.
- Removed user-profile entitlement editing hooks and paid UI text. Donation state is not read by access, quota, ranking, source or support code.
- Added `SCHA_Four_Plan_Compliance` with fixed governing-plan IDs, canonical capabilities, consumed owners, `clinical_authority=false`, `single_free_tier=true`, and `donor_advantage=false`.
- Added an institutional AI Teacher identity that is explicitly nonhuman, not a verified doctor and has no clinical authority.
- Added versioned cross-owner contracts for File 20, File 21, File 22, File 19 and File 26; no duplicate post/search/notification backend was created.
- Upgraded release to 2.1.0, schema 2.1.0, WordPress 7.0 and PHP 8.3.

**Round result:** corrected; no known repository-owned constitution, business-law or ownership blocker.

## Review round 2 — Security, identity, privacy and clinical safety

### Defects found

| ID | Severity | Defect |
|---|---:|---|
| R2-D01 | Critical | Protected actions did not re-evaluate live File 00 suspension, revocation, guardian, consent and risk state. |
| R2-D02 | Critical | Guest REST requests accepted an empty WordPress nonce, allowing CSRF-like cross-site use of guest sessions. |
| R2-D03 | Critical | Session questions and answers were stored as plaintext. |
| R2-D04 | High | Input safety existed, but provider output could still emit diagnosis, remedy, potency, dose, frequency or secret material. |
| R2-D05 | High | Retention and erasure lacked an explicit legal/security-hold path. |
| R2-D06 | Medium | Usage ledger could receive redacted prompt text as the request-hash input rather than an irreversible request digest. |

### Corrections applied

- Added `SCHA_Account_Context`; every session create/open/answer/list path consumes current `sabri_membership_claims_v2`, fails closed when the membership owner is unavailable, and rejects blocked/risk/guardian/consent-ineligible state. Founder governance remains explicit.
- Added `SCHA_Guest_Auth` with an HttpOnly SameSite cookie binding and hourly HMAC request token carried in `X-SCHA-Guest-Token`.
- Added authenticated encryption (`scha2` envelope; XChaCha20-Poly1305 where available, AES-256-GCM fallback) for stored session messages with legacy plaintext read compatibility.
- Added `SCHA_Output_Policy` after provider generation and before citation acceptance; unsafe output is withheld, never merely labelled.
- Added `legal_hold` to session state; retention skips held records, user deletion returns an explicit conflict, and WordPress erasure reports retained items honestly.
- Request telemetry now stores only HMAC/SHA-256 digests, not raw or redacted prompt bodies; audit contexts remain scrubbed.

**Round result:** corrected; input/output clinical boundaries, identity revalidation, guest authorization, encrypted storage and privacy lifecycle are represented in source and tests.

## Review round 3 — Top-20 value, AI Teacher, UX, discovery and accessibility

### Defects found

| ID | Severity | Defect |
|---|---:|---|
| R3-D01 | Critical | Missing Claude provider and four-daily-post AI Teacher queue/review/publication lifecycle. |
| R3-D02 | High | Assistant supported only one generic mode, omitting learning tutor, search assistant, creator assistant and doctor nonclinical administration. |
| R3-D03 | High | Search projection lacked “why this result,” freshness and explicit no-paid/no-donor/no-clinical-ranking metadata. |
| R3-D04 | Medium | Back/Home controls did not first consume the current File 20 owner contract. |
| R3-D05 | Medium | No public accessibility statement, reduced-data behavior or explicit low-bandwidth contract. |
| R3-D06 | Medium | Public UI retained superseded pricing language and did not visibly distinguish the AI Teacher from a human/verified doctor. |
| R3-D07 | Medium | Private/degraded routes relied mainly on an HTML robots tag rather than complete header/cache controls. |

### Corrections applied

- Added a fixed Anthropic Messages API adapter using server-only `SCHA_ANTHROPIC_API_KEY`; no endpoint override or secret is exposed to browser/repository.
- Added `SCHA_AI_Teacher` with exactly four configurable daily slots, idempotent schedule keys, queue claim, exponential retry, provider budget, source retrieval, rights/medical/Sharīʿah/output/citation gates, audit/outbox events and canonical publishing-owner handoff.
- Enforced at least 30 days of mandatory human review. Later low-risk auto-publication requires all of: elapsed review period, enabled setting, low-risk category, approved rights, affirmative Sharīʿah gate, explicit Founder policy hook and successful File 21 owner adapter. Otherwise the draft remains review-required/approved and is handed to File 22; no duplicate WordPress post is created.
- Added study, learning tutor, knowledge-search, creator and verified-doctor nonclinical-administration modes. All remain source-grounded and nonclinical.
- Added File 26 search projection metadata: canonical owner, freshness, `why_this_result`, and no paid/donor/clinical ranking.
- Added current `sabri_file20_context_controls_markup_v1` consumption before legacy/fallback Back/Home rendering.
- Added localized public strings, logical RTL layout, mirrored direction icon, 44px targets, reduced motion, forced colors, reduced-data/low-bandwidth mode and a public accessibility statement.
- Added `wp_robots`, `X-Robots-Tag`, no-store/private cache, referrer, MIME and permissions headers for protected/degraded surfaces.
- Public UI now states one free tier and donor neutrality and labels the AI Teacher as nonhuman, not a verified doctor and human-governed.

**Round result:** corrected; relevant Top-20 value is adapted without importing addictive ranking, paid influence, impersonation, a duplicate search owner or a duplicate publishing backend.

## Review round 4 — Data, migration, release engineering, regression and evidence truth

### Defects found

| ID | Severity | Defect |
|---|---:|---|
| R4-D01 | High | Schema lacked assistant mode, claims version, legal hold, message encryption version and AI Teacher state. |
| R4-D02 | High | Existing tests did not freeze the four-plan corrections or prohibit paid-access regression. |
| R4-D03 | High | No clean deterministic 2.1.0 package/SBOM/four-plan audit evidence existed. |
| R4-D04 | Medium | Health checks omitted the AI Teacher scheduler and current WordPress/PHP baseline. |
| R4-D05 | Medium | Historical repository name still describes a temporary bridge, while the dedicated plan’s canonical repository name is `16-sabri-classical-homeopathy-ai`.

### Corrections applied

- Added idempotent schema 2.1.0 and a governed `teacher_posts` table. Default uninstall remains non-destructive; guarded purge includes the new table and schedule.
- Added unit/adversarial tests, four-plan static/negative-regression tests, package contracts, PHP/JS syntax checks, secret-pattern checks and deterministic double-build comparison.
- Added architecture, API, data schema, security, threat model, privacy, accessibility, operations, migration/rollback, traceability, review evidence, release checklist, source manifest and SPDX SBOM.
- Health now reports WordPress 7.0/PHP 8.3 compatibility, schema, provider, corpus, dead letters, teacher/outbox/retention scheduler and the four-plan manifest.
- Canonical plugin/package folder, slug and PHP namespace are correct. The historical GitHub repository URL is retained as a compatibility alias because repository rename is an external GitHub administration action; documentation forbids silent identity change.
- Added an atomic `approved/publish_pending → publishing → published` claim, canonical idempotency key and cron retry for owner-unavailable AI Teacher publication; the module still never creates a duplicate File 21 post.
- Added canonical corpus-owner allowlisting, required approved-use, rights evidence/review date, sensitive-data exclusion, metadata re-review and fail-closed approval/indexing.
- Corrected privacy erasure to process non-held sessions to completion, report held sessions separately, forbid ordinary deletion of held sessions and pseudonymize owner-linked metadata after retention expiry.
- Removed the hard-coded Claude model identifier: a reviewed model ID is now explicit configuration. The generic HTTPS provider now validates safe URL, exact allowlisted host, port 443 and all resolved A/AAAA addresses.
- Added recursive audit/outbox scrubbing and separated institutional AI Teacher usage (`session_id=0`) from guest user budget summaries.

**Round result:** after local retest, zero known unresolved repository-source blocker. Exact-head GitHub CI, Hostinger staging install/upgrade, real File 00/20/21/22/19/26 contracts, configured provider/corpus, browser/screen-reader/load/restore/rollback evidence, Founder acceptance, live deployment and operational SLO evidence remain separate external gates and are not misrepresented as completed.

## Final repository-level verdict

- Four-plan source/code trace: complete for the defined File 16 repository scope.
- Known repository-owned blocking defects after correction: **0**.
- Coded/packaged status: candidate complete.
- CI/staging/live/operational status: must be proven independently by their respective gates.
