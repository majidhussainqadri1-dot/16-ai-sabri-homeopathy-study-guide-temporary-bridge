# Threat Model

Threats addressed: IDOR/session enumeration, CSRF guest use, stale verified claims, prompt injection, data exfiltration, malicious corpus instructions, fabricated citations, provider hallucination, clinical instruction output, SSRF, secret leakage, quota/cost abuse, duplicate publication, AI impersonation, donor/paid influence, retention bypass and audit PII leakage.

Trust boundaries: browser↔WordPress REST; WordPress↔File 00/content owners; retrieval DB; WordPress↔external provider; File 16↔publication/search/notification owners. Controls are layered; staging penetration and companion contract tests remain mandatory.
