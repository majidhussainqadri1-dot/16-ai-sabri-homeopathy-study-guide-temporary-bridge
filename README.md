# File 16 — Sabri Classical Homeopathy AI

Production-oriented WordPress implementation of **File 16** for the **Sabri Social Homeopathy Platform**.

## Release status

- Software version: **1.0.0**
- Schema version: **1.1.0**
- Plan: **SSH-F16-PLAN-2026-v1.0**
- Canonical plugin folder: `16-sabri-classical-homeopathy-ai`
- Public route: `/ai/`
- Private routes: `/ai/session/{uuid}/`, `/ai/history/`
- Governance route: `/ai/governance/`

This repository implements both the governed temporary Custom GPT bridge and the native source-linked AI foundation. It does not claim staging acceptance, production deployment, configured provider availability, or an approved populated corpus without corresponding environment evidence.

## Implemented capability domains

- Separate AI entitlement and quota controls
- Account-owned and cryptographically bound guest sessions
- Prompt intake, multilingual safety classification, PII redaction and prompt-injection resistance
- Approved, versioned, licensed and access-labelled corpus registry
- Chunking, indexing, lexical retrieval and source provenance
- Citation validation and evidence-insufficient fail-closed behavior
- Local extractive provider, strict Custom GPT bridge and allowlisted HTTPS JSON provider
- Provider/model abstraction, cost budget, rate limits and usage ledger
- Retention, export, erasure, feedback, escalation and WordPress privacy integration
- Evaluation suite, health report, audit trail, transactional-style outbox and operational metrics
- Accessible responsive UI, RTL support, green primary visual identity and shared-shell integration contracts

## Local verification

```bash
bash scripts/verify.sh
bash scripts/build.sh
```

The build script creates a deterministic ZIP and SHA-256 checksum under `dist/`.

## Deployment boundary

Install and test on the approved staging site first. Production activation requires a restorable backup, migration verification, provider/corpus configuration, real-role journey tests, security and privacy acceptance, rollback proof, and Founder approval.
