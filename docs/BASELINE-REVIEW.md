# Mandatory Baseline Review

## Review objective

Establish whether the exact supplied File 16 package is safe, internally consistent, correctly integrated, and acceptable for staging before any correction or enhancement branch is merged.

## Required checks

1. Confirm archive checksum and extracted-file checksums.
2. Confirm plugin header version, `readme.txt` stable tag, and package filename all identify version `0.1.0`.
3. Install the exact ZIP on a fresh WordPress staging site with the required platform foundation active.
4. Confirm activation completes without fatal error, warning, unexpected page overwrite, or permission failure.
5. Confirm the public AI Study Guide page is created or safely updated at the intended slug.
6. Confirm only an HTTPS `chatgpt.com/g/` URL is accepted.
7. Confirm invalid URLs fail closed and restore the approved provider URL.
8. Confirm the external fallback opens securely with `noopener` and `noreferrer` where applicable.
9. Confirm iframe failure never leaves the user at a dead end.
10. Confirm the floating button appears only on the intended Home Page conditions.
11. Confirm modal keyboard behavior, focus handling, Escape behavior, labels, and reduced-motion behavior.
12. Confirm the temporary bridge page emits the approved `noindex` and `noarchive` controls.
13. Confirm the privacy warning prohibits patient-identifying information.
14. Confirm no API key, secret, patient record, diagnosis engine, prescription engine, potency instruction, or dosage instruction exists in this release.
15. Confirm deactivation and uninstall affect only plugin-owned data and do not damage companion-module data.
16. Confirm cache clearing, backup restoration, rollback, responsive layouts, and cross-browser behavior.

## Acceptance law

Any defect, contradiction, regression, security/privacy issue, incomplete workflow, or blocker discovered by this review must be corrected and re-tested before progression.
