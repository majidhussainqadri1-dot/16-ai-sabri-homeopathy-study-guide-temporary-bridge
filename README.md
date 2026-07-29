# File 16 — AI Sabri Homeopathy Study Guide Temporary Bridge

Official source repository for **File 16** of the **Sabri Social Homeopathy Platform**.

## Plugin

- **Name:** AI Sabri Homeopathy Study Guide Temporary Bridge
- **Version:** 0.1.0
- **WordPress folder:** `ai-study-guide-bridge`
- **Purpose:** Provide a branded, accessible, temporary bridge from the Sabri Social Homeopathy Platform to the approved AI Sabri Homeopathy Study Guide hosted on ChatGPT while the native platform AI is developed.

## Baseline capabilities

- Branded public Study Guide page
- Main navigation integration
- Home Page floating access button and accessible modal
- Best-effort iframe attempt with a permanent secure external fallback
- Strict `chatgpt.com/g/` URL validation
- Settings page for bridge controls
- Medical-safety and privacy notices
- `noindex` and `noarchive` controls for the temporary bridge
- Provider abstraction reserved for a future native AI module
- No API key and no native AI implementation in this release

## Repository layout

- `ai-study-guide-bridge/` — installable WordPress plugin source
- `SOURCE-PROVENANCE.md` — source origin and integrity record
- `ORIGINAL-ARCHIVE.sha256` — supplied archive identity
- `MANIFEST.md` — file inventory
- `CHECKSUMS.sha256` — SHA-256 verification list for the imported source
- `STATUS.md` — baseline status and acceptance limits
- `docs/BASELINE-REVIEW.md` — mandatory post-import review record

## Installation

Create an installable ZIP whose single top-level folder is `ai-study-guide-bridge/`, then upload it through **WordPress Admin → Plugins → Add New → Upload Plugin**.

## Governance

This repository preserves the supplied File 16 baseline. Baseline import does not by itself mean production acceptance. Staging installation, runtime verification, integration testing, privacy review, responsive testing, rollback testing, and Founder acceptance remain separate gates.
