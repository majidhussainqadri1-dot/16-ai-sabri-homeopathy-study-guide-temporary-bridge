# API and Integration Contracts

## REST `scha/v1`

- `POST /sessions` `{mode}`
- `GET /sessions`
- `GET|DELETE /sessions/{uuid}`
- `POST /sessions/{uuid}/answer` `{prompt,idempotency_key}`
- `GET /sessions/{uuid}/export`
- `POST /feedback`
- `GET /sources`, `/usage`, restricted `/health`

Authenticated requests require `X-WP-Nonce`; guest demo requests require `X-SCHA-Guest-Token`. All private endpoints are owner-scoped.

## Cross-module filters/actions

- `sabri_membership_claims_v2` — File 00 current claims.
- `sabri_file20_context_controls_markup_v1` — File 20 Back/Home controls.
- `sabri_file21_publish_ai_teacher_post_v1` — canonical publication result.
- `sabri_file22_ai_teacher_draft_ready_v1` — review-ready draft handoff.
- `sabri_search_provider_documents_v1` — File 26 public source projection.
- `sabri_institutional_profiles_v1` — nonhuman AI Teacher profile.
- `sabri_module_registry_v1` — four-plan manifest.
- `sabri_file16_register_grounded_profile_context_provider` — accepts File 03's public-only nonrecursive professional-work source provider.
- `sabri_file16_grounded_profile_ask_v1` — returns a fresh, subject-bound, source-cited `public_professional_work` claim for File 03; no diagnosis/prescription/dose/emergency authority.
