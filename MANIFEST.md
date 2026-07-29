# File Manifest

## Original plugin source

| Path | Purpose |
|---|---|
| `ai-study-guide-bridge/ai-study-guide-bridge.php` | WordPress plugin bootstrap, metadata, constants, includes, activation hooks, runtime start |
| `ai-study-guide-bridge/assets/css/study-guide.css` | Public bridge page, modal, and floating-button presentation |
| `ai-study-guide-bridge/assets/js/study-guide.js` | Public modal and bridge interactions |
| `ai-study-guide-bridge/includes/class-sai-activator.php` | Activation, deactivation, page creation/update, and initial settings |
| `ai-study-guide-bridge/includes/class-sai-admin.php` | WordPress settings registration and administration interface |
| `ai-study-guide-bridge/includes/class-sai-frontend.php` | Public page, navigation, assets, metadata, and floating access integration |
| `ai-study-guide-bridge/includes/class-sai-helpers.php` | Defaults, validation, settings, URLs, and shared helpers |
| `ai-study-guide-bridge/includes/class-sai-plugin.php` | Hook orchestration |
| `ai-study-guide-bridge/includes/class-sai-privacy.php` | WordPress privacy-policy content integration |
| `ai-study-guide-bridge/includes/class-sai-provider.php` | Temporary Custom GPT provider abstraction and future native mode boundary |
| `ai-study-guide-bridge/readme.txt` | WordPress installation, scope, safety, privacy, and changelog documentation |
| `ai-study-guide-bridge/templates/floating-modal.php` | Accessible floating modal template |
| `ai-study-guide-bridge/templates/study-guide.php` | Public Study Guide page template |
| `ai-study-guide-bridge/uninstall.php` | Controlled uninstall cleanup |

## Repository-only governance files

- `.github/workflows/baseline-integrity.yml`
- `.gitignore`
- `README.md`
- `SOURCE-PROVENANCE.md`
- `ORIGINAL-ARCHIVE.sha256`
- `STATUS.md`
- `MANIFEST.md`
- `CHECKSUMS.sha256`
- `docs/BASELINE-REVIEW.md`
