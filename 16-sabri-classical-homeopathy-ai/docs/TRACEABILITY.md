# File 16 Requirement Traceability

| Requirement | Implementation evidence |
|---|---|
| F16-FR-001 Separate entitlement | `SCHA_Entitlements`, user entitlement administration, explicit basic-membership exclusion |
| F16-FR-002 Session management | `SCHA_Session_Service`, sessions/messages schema, history/export/delete |
| F16-FR-003 Prompt intake | REST validation, `SCHA_Prompt_Policy`, rate and size limits |
| F16-FR-004 Corpus registry | `SCHA_Corpus`, owner/version/license/language/access/checksum/status fields |
| F16-FR-005 Ingestion/indexing | governed text normalization, chunking, checksums and reindex/retraction |
| F16-FR-006 Retrieval | `SCHA_Retrieval`, role/access filters, bounded diversified results |
| F16-FR-007 Citations | `SCHA_Citation_Validator`, source/version/location resolution and fail-closed withholding |
| F16-FR-008 Provider abstraction | local, bridge and HTTPS JSON adapters through `SCHA_Provider_Registry` |
| F16-FR-009 Safety/refusal | multilingual emergency, diagnosis, prescription, potency/dosage and injection policies |
| F16-FR-010 Privacy | PII redaction, private-route headers, WP exporter/eraser and corpus exclusions |
| F16-FR-011 Usage/cost | transparent ledger, quotas, rate limits and monthly provider budget |
| F16-FR-012 Feedback/correction | owned-message feedback, escalation events and governance audit |
| F16-FR-013 Evaluations | built-in multilingual adversarial evaluation dataset and persisted metrics |
| F16-FR-014 Temporary bridge | strict `chatgpt.com/g/` validation and external-provider disclosure |
| F16-FR-015 Native migration | stable session/entitlement/provider semantics independent of bridge |
| F16-FR-016 Accessibility/RTL | semantic templates, keyboard targets, focus styles, responsive logical layout |
| F16-FR-017 Integrations | route, navigation and File 26 search contracts plus versioned outbox events |
| F16-FR-018 Operations | health report, audit, schedules, dead-letter state, CI and rollback docs |
| F16-FR-019 Packaging/release | canonical folder, semantic version, deterministic ZIP/checksum and release gates |
