# File 16 — Forty-Round Review, Immediate Correction and Fresh-Retest Register

**Date:** 2026-08-07 (Pakistan Standard Time)  
**Corrective release:** 2.2.0 / schema 2.1.0  
**Repository scope:** Sabri Classical Homeopathy AI — File 16  
**Method:** Every round was performed against the governing four-plan corpus. When a defect was found, the repository-owned defect was corrected before the next round and the affected source was syntax/static retested immediately. Final source/package regression follows round 40.

## Governing precedence

1. Definitive Master Plan v3.0.
2. Recovered Founder Directives v2.1 and later explicit Founder decisions.
3. Continuous Value / Global Top-20 Superset v1.0.
4. File 16 Complete Master Plan v1.0, except where superseded by a later governing directive.

The later one-complete-free-tier/no-donor-advantage directive supersedes the historical paid AI add-on language. Repository/package/CI evidence never substitutes for Hostinger staging, live deployment or operational acceptance.

## Forty rounds

| # | Review focus | Result | Immediate correction / retest |
|---:|---|---|---|
| 1 | Governing precedence and supersession | Clean | Four-plan precedence remained explicit; no code correction required. |
| 2 | One free tier, donation neutrality, no paid influence | Clean | No executable paid/add-on or donor-bias path found. |
| 3 | Canonical ownership / no duplicate backend | Clean | File 20/21/22/19/26 boundaries remained non-owning integrations. |
| 4 | 19 functional-requirement traceability | Clean | All F16-FR-001…019 remained represented. |
| 5 | 10 NFRs and lifecycle/status truth | Clean | Coded/package/QA were still kept separate from staging/live/operational. |
| 6 | File 00 live claims, Founder identity and privilege | **Defect found** | Removed local WP/admin capability as Founder identity; removed Founder bypass of File 00 approval; verified-doctor privilege now requires approved live claims. PHP lint passed. |
| 7 | Guest HMAC/cookie authorization | Clean | HttpOnly/SameSite binding and request token remained in place. |
| 8 | Session ownership, guest binding and IDOR | Clean | Owner/guest session checks remained fail-closed. |
| 9 | At-rest authenticated encryption and legacy handling | **Defect found** | Removed user-controlled `scha2:` encryption bypass; only valid algorithm envelopes count as encrypted. Crypto lint/unit retest passed. |
| 10 | REST privacy, cache and indexing controls | **Defect found** | Added no-store/no-cache/noindex headers to sensitive REST responses and post-dispatch error responses; usage revalidates File 00. PHP lint passed. |
| 11 | Browser-side minimization / anti-fingerprinting | **Defect found** | Removed language/screen/time-zone client fingerprint header. JS syntax passed. |
| 12 | Browser storage failure resilience | **Defect found** | Guarded `localStorage` access so privacy/storage exceptions cannot break the AI interface. JS syntax passed. |
| 13 | Public/restricted source catalogue and cache isolation | **Defect found** | Made public catalogue setting effective; authenticated source catalogue requires approved File 00 state and receives private/no-store/noindex protections. PHP lint passed. |
| 14 | Input clinical-safety classification EN/UR/AR | **Defect found** | Expanded individual remedy/medicine/treatment request detection in English, Urdu and Arabic. Unit/adversarial retest passed. |
| 15 | Provider-output clinical/secret safety | **Defect found** | Expanded recommendation/personal-remedy output blocking in EN/UR/AR. Unit/adversarial retest passed. |
| 16 | Citation validity and claim coverage | **Defect found** | Added substantive-block citation coverage gate; a long multi-claim answer can no longer pass with one token citation elsewhere. Unit/static retest passed. |
| 17 | Corpus rights/access metadata and re-review | **Defect found** | Access/source/title/language/rights changes now reset approval/index state and require re-review; write failures are checked. PHP lint passed. |
| 18 | Retrieval ACL consistency | **Defect found** | Retrieval now enforces both current item ACL and chunk ACL, preventing stale-chunk privilege leakage. PHP lint passed. |
| 19 | Retrieval source diversity | **Defect found** | First pass selects one chunk per source; bounded second pass allows at most two/source. PHP lint passed. |
| 20 | Generic HTTPS provider SSRF/DNS/port defenses | Clean | HTTPS, exact host allowlist, port 443 and A/AAAA public-address checks remained present. |
| 21 | External-provider response memory bound | **Defect found** | Added 1 MiB response-size ceiling to Claude and generic HTTPS provider requests. PHP lint passed. |
| 22 | Provider/model disclosure and secret configuration | Clean | Server-only secrets, reviewed model configuration and provider disclosure remained intact. |
| 23 | Rate-limit concurrency and guest identity | **Defect found** | Replaced racy transient-only check with per-identity DB advisory lock; removed User-Agent fingerprint dependency. PHP lint passed. |
| 24 | Policy-version atomicity and active-policy integrity | **Defect found** | Policy activation is transactional, write failures checked, active option updated only after commit; policy version advanced to 2.2.0. PHP lint passed. |
| 25 | Exactly four governed AI Teacher slots | Clean | Settings sanitization and scheduler retained exactly four slots. |
| 26 | AI Teacher stale generation worker recovery | **Defect found** | Added 20-minute stale `generating` lease recovery with retry/audit. PHP lint passed. |
| 27 | AI Teacher auto-publication authorization boundary | **Defect found** | Removed public boolean bypass; automatic publishing is private/internal and requires `publish_pending`, while human publishing requires capability + `approved`. PHP lint passed. |
| 28 | AI Teacher review/reject/retry state machine | **Defect found** | Added conditional state transitions; published content cannot be “rejected” locally; manual retry limited to failed generation. PHP lint passed. |
| 29 | AI Teacher publication retry accounting/backoff | **Defect found** | Publication claims increment attempts; exponential backoff uses actual publication attempt; retry-state write failures are surfaced. PHP lint passed. |
| 30 | File 21/22 handoff, idempotency and duplicate-post prevention | Clean | Canonical File 21 handoff and idempotency key remained intact; no duplicate WordPress post backend found. |
| 31 | Transactional outbox crash recovery | **Defect found** | Added processing lease/recovery; expired workers return to retry; delivery/finalization transitions are conditional and fail safely. PHP lint passed. |
| 32 | External-provider deletion lifecycle | **Defect found** | Added explicit `confirmed/not_applicable/pending/failed` deletion-result contract and audit instead of fire-and-forget overclaim. PHP lint passed. |
| 33 | Local privacy erasure/retention transaction integrity | **Defect found** | Added explicit DB write/commit failure checks, rollback, provider-deletion pending event and honest erasure reporting. PHP lint passed. |
| 34 | Feedback ownership and semantic integrity | **Defect found** | Feedback can now target only an assistant message belonging to the user’s session. PHP lint passed. |
| 35 | File 20 Back/Home/context-control integration | Clean | Current File 20 markup contract is consumed first; safe same-origin fallback remains. |
| 36 | File 26 discovery explainability and bias neutrality | Clean | Public-source projection retains `why_this_result`, freshness and no paid/donor/clinical ranking. |
| 37 | UI escaping, accessibility, green brand, RTL and low-bandwidth | Clean | Escaping, 44px targets, logical properties, visible focus, reduced motion/data and green identity remained represented. |
| 38 | Schema migration, uninstall, package/SBOM integrity | **Defect found** | Replaced transient migration race with DB advisory lock; schema version is recorded only after post-migration invariants hold; activation/health honor migration failure. Release/SBOM advanced to 2.2.0 while schema remains 2.1.0. |
| 39 | Automated regression/CI coverage of new corrections | **Defect found** | Added forty-round negative-regression test, expanded four-plan assertions, version/package checks and CI execution for the new suite. |
| 40 | Fresh end-to-end repository regression after all fixes | Clean | Full PHP/JS/static/unit/contract/forty-round/package/secret/deterministic-build/clean-extract/archive checks pass before release candidate acceptance. |

## Count required by Founder

- **Rounds in which one or more defects were found and corrected:** **25**
- **Rounds in which no new defect was found:** **15**
- **Total review rounds:** **40**

The count is by review round, not by individual defect. Some defect-bearing rounds contained more than one related flaw.

## Release boundary

After round 40, the repository-owned source/package candidate has zero known unresolved blocking defect within the reviewed scope. This statement does **not** mean staging-accepted, live-deployed or operational. Those statuses still require real Hostinger/WordPress installation/upgrade, real File 00/19/20/21/22/26 contracts, configured provider/corpus, browser/assistive-technology/load/security evidence, backup/restore/rollback rehearsal and Founder acceptance.
