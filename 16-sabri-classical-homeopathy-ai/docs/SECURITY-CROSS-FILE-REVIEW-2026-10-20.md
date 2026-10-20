# File 16 Cross-File Security, 40-Checkpoint Review — 2026-10-20

## Scope and source hierarchy
- Reviewed baseline GitHub `main` HEAD: `0cea92273f9e716ba45e3606b3afaa9751c2d88d`.
- Corrective source candidate `2.2.2`; DB schema stays `2.1.0`.
- Governing documents: consolidated central plans, File 16 dedicated master plan, and File 03/00/02/05/06/12/15/19/20/21/22/24/26 ownership and integration requirements. Related File 14/17/18 requirements were examined for nonduplication of data ownership, medical safety, and privacy. No independent File 14/17/18 backend is created.
- Historical 2026-08-07 forty-round audit is **not** claimed as the new audit. Fresh `tests/forty-scope-regression.php` has exactly 40 named, independent source-to-plan static checkpoints. They are not 40 full integration/staging end-to-end cycles.

## Confirmed source defects and corrections

| ID | Severity | Root cause and evidence | Source correction | Verification |
| --- | --- | --- | --- | --- |
| F16-20261020-01 | Critical | `SCHA_Session_Service::owned` compared guest owner_id 0 to unauthenticated user_id 0 and accepted a session even when `guest_ok` was false | Deny a guest-owned session unless its actual guest cookie binding passes (manager override is separate) | PHP cross-guest ownership negative/positive assertions |
| F16-20261020-02 | High | AI Teacher retrieved `public,subscriber,doctor,founder` using artificial Founder entitlement before creating public educational posts | Only retrieve `public` corpus for public institutional posts | Source assertion on retrieval access class; staging with mixed-ACL corpus still needed |
| F16-20261020-03 | High | Stale AI Teacher `publishing` workers recovered unconditionally to `publish_pending`, allowing cron to auto-publish a previously human-reviewed workflow without preserving its provenance | Return human-reviewed work to manual `approved`; all unreviewed abandoned publication to `review_required` | Source negative regression; staging interrupted-worker crash/replay still needed |
| F16-20261020-04 | High | File 03 URL validation checked only host plus weak two-field userinfo `isset`; allowed downgrade/port mismatch | Exact scheme, host and effective port; reject each userinfo part | PHP reflection tests for HTTPS positive and HTTP, other host, other port, userinfo, script URL negatives |

## Forty independently checked areas
The enforceable list and results live in `tests/forty-scope-regression.php`: (01) release entry, (02–03) 19 FR/10 NFR trace, (04–05) free/donor policy, (06–10) identity and REST/guest privacy, (11–20) encryption/source rights/clinical safety/citation/retrieval ACL, (21–25) provider/SSRF/rate/budget, (26–30) AI Teacher schedule/public corpus/human review/recovery/canonical publication, (31–35) File 22/20/19/26/03 integrations, (36–40) strict origin, RTL, noindex, migration and evidence status. All 40 must pass to consider this source-level contract gate green; the dynamic behavioral security suite is separate.

## Remaining release obstacles
1. **Exact-head CI verification** on the branch/PR, including PHP 8.3, JS syntax, PHP behavioral regressions and 40 named checkpoints.
2. **Build parity**: tracked `dist/16-sabri-classical-homeopathy-ai-2.1.0.zip` (if present) or `2.2.1.zip` cannot carry these fixes. Use only an independently produced and checksummed `2.2.2` artifact.
3. **Staging environment**: confirm actual installed plugin build, File 00 claims and schema/migrations, approved public/restricted corpus, File 03/19/20/21/22/26 owner contracts, provider credentials, failed/crashed workers, consent/erasure, browser/RTL/low bandwidth, load, backup restore and rollback.
4. **Production gate**: Founder-authorized deployment, post-deploy smoke tests, exact artifact/checksum and DB migration parity; active version and live defects cannot be assumed from GitHub.
5. **Unreproduced broad promise**: static source scans and limited local/CI tests do not establish all runtime features against external modules.

## Status integrity
- Repository HEAD at initial investigation: recorded above.
- Branch source candidate: 2.2.2 (commit SHA is recorded in GitHub PR).
- Deployed plugin: unverified.
- Deployed database version: unverified.
- Migration state: unverified.
- Live verification: **NOT PERFORMED**.
- Do not use the term 'Resolved on live' until deployment and live re-test prove it.
