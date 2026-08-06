# Architecture

File 16 owns AI sessions, provider/model routing, approved-corpus retrieval, citation validation, usage/cost records, AI feedback, evaluations and AI-specific privacy controls. Files 05, 06, 12, 15 and approved research sources remain canonical owners of their knowledge objects.

The request path is: entitlement → owned session → idempotency/rate gate → prompt safety → privacy minimization → access-filtered retrieval → provider → citation validation → message/usage persistence → outbox events.

WordPress is the canonical application core. External providers are replaceable adapters and never canonical knowledge owners. Secrets remain outside the repository.
