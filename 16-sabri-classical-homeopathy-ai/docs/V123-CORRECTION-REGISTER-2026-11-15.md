# File 16 — V12.3 Correction and Acceptance Register
Date: 2026-11-15 (Pakistan)  
Source base: `main@0cea92273f9e716ba45e3606b3afaa9751c2d88d`  
Review branch: `audit/file16-v123-source-hardening-20261115`  
Scope: GitHub source, tests and reproducible CI package — **not** deployed/staging/runtime acceptance.

## Confirmed repository defects corrected

| # | Severity | Source defect | Changed source | Verification scope |
|---|---|---|---|---|
| 01 | Critical | DB `scha_outbox` physically creates `payload_json` but schema invariant expected `payload`; fresh install/upgrades could fail | `class-scha-database.php` | New `tests/schema-contract.php` + CI |
| 02 | Critical | Anonymous `owner_id=0` matched current anonymous ID even with an invalid guest binding, allowing access to another guest's known UUID | `class-scha-session-service.php` | Isolated cross-account `tests/guest-session-authorization.php` |
| 03 | Critical | AI Teacher's public posting retrieved private `doctor`/`founder` corpus classes | `class-scha-ai-teacher.php` | New source-privacy contract; staging still required |
| 04 | High | External AI providers could receive sensitive substrings in cited corpus excerpts | `class-scha-provider-claude.php`, `class-scha-provider-http-json.php` | Server-side denial before request; provider integration pending |
| 05 | High | Corpus approval could succeed despite failed chunk writes, event write or commit | `class-scha-corpus.php` | Checked transaction/outbox and rollback; live DB chaos pending |
| 06 | High | Long paragraphs and more than 10,000 chunks could be silently truncated/oversized | `class-scha-corpus.php` | Unicode chunk regression in `tests/run.php` |
| 07 | High | An unrelated factual paragraph could pass solely with a syntactically valid source marker | `class-scha-citation-validator.php` | Limited lexical overlap regression (NOT entailment) |
| 08 | High | The same canonical owner/item/version could silently change its content, invalidating earlier citations | `class-scha-corpus.php` | Version-conflict fail-closed guard; owner integration pending |
| 09 | Medium | Free-text feedback could persist contact identifiers and secrets | `class-scha-feedback.php` | Redaction prior to persistence |
| 10 | Medium | Public portfolio context could include identifiers forwarded to an external provider | `class-scha-integration.php` | Sensitive-source rejection; File 03 integration pending |
| 11 | Medium | Local CSS/settings defaults used an obsolete green value rather than V12.3 `#087A4E` | `assets/css/public.css`, `class-scha-settings.php` | Source review only; visual browser acceptance pending |
| 12 | Medium | Root README described stale source release 2.1.0 even though plugin is 2.2.1 | `README.md` | Exact source-header alignment |

## Verification performed by branch CI

The `audit/**` GitHub Actions workflow performs PHP 8.3 syntax lint, JavaScript checks, unit/adversarial tests, source contracts, package checks, secrets-pattern scan, deterministic double-build, ZIP extraction/manifest integrity and package upload. New checks include schema-to-DDL validation, cross-account guest IDOR regression, privacy-source checks, expanded 44-example Urdu/English/Arabic safety evaluation and long-Unicode-source chunking.

A green CI result certifies only *that exact commit* and those checks. No live WordPress database, deployment package, active plugin, configuration, migration, external provider responses, accessibility devices or production performance was examined.

## Unfinished plan obligations — not to be closed by a source-only claim

1. **F16-FR-005/006:** Real vector embeddings and semantic/hybrid retrieval, provider agreement, backfill and ranking evaluation; current lexical retrieval remains.
2. **F16-FR-007/016 and F16-CEN-02:** Claim-level source entailment, source/claim freshness, evidence conflict, multilingual paraphrase and actual provider hallucination/bias benchmarking. The implemented lexical overlap filter is intentionally insufficient for these goals.
3. **F16-FR-012, NFR-003/004:** Provider-risk-aware queuing, retry-after cost limits, concurrency/chaos testing and p75/p95 measurement with an exact staging profile.
4. **F16-NFR-005/010:** Complete WCAG 2.2 AA, Urdu/Arabic translations, real devices, keyboard, screen readers, dark theme, forced-colors and responsive acceptance.
5. **F16-NFR-007/009:** Verify exact deployed plugin/PHP/WordPress versions, `scha_schema_version`, tables, options, migration/rollback, checksum and staging/live parity before deployment.
6. **Cross-file contracts:** Run real integration journeys against File 00, 03, 05, 06, 12, 19, 20, 21, 22, 24, 25 and 26.
7. **Release governance:** Founder approves user-facing copy/appearance and any production deployment only after staging and backup/restore evidence.

## Non-negotiable live reality report

Repository HEAD: record exact CI SHA before merge.  
Deployed Version: **unverified**.  
DB Version: **unverified** (source declares 2.1.0).  
Migration State: **unverified**.  
Live Verification Status: **not performed**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

Repository CI success is not live defect resolution. Do not silently deploy this branch or claim 100% completion.
