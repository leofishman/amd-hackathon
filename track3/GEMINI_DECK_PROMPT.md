# Prompt para Gemini — slide deck Track 3 (exportar a PDF)

Pegá esto en Gemini tal cual. Después exportá a PDF: el pre-screening
automático LEE el PDF, así que el texto de las slides importa más que el
diseño — nada de texto dentro de imágenes.

---

Create a 10-slide pitch deck for a hackathon submission (AMD Developer
Hackathon, Track 3: Open Innovation). Clean, professional, dark theme.
All content in English. One idea per slide, minimal text, no filler.

**Slide 1 — Title.** "Drupal AI Factchecker — claim-level verification on
your own corpus, powered by AMD." Team: Drupal AI Router (Leo Fishman).
GPL-2.0+, published on drupal.org.

**Slide 2 — The problem.** Universities pay heavily for plagiarism
detection (Turnitin et al.), but plagiarism tools detect *copying*, not
*falsehood*. AI-generated essays are original text full of fabricated
claims — invisible to every plagiarism scanner. Editors and educators have
no in-workflow tool to verify factual accuracy.

**Slide 3 — The insight.** The institutions with this problem already run
Drupal (governments, universities — cite: Drupal powers a large share of
.edu and .gov sites). The verification tool should live where the content
lives, not in another SaaS tab.

**Slide 4 — The product.** A content-integrity suite native to Drupal: one
click on any content node runs three checks. (1) Factcheck: atomic claim
extraction → evidence retrieval → per-claim verdict (SUPPORTED /
UNSUPPORTED / CONTRADICTED) with auditable score. (2) AI-writing
likelihood: 0–100 score with rationale (honest framing: a hint, not
proof). (3) Verbatim plagiarism: the text's most distinctive sentences
searched as exact phrases on the web. Screenshot placeholder: the "Content
scan" tab.

**Slide 5 — What makes it different.** (1) Evidence cascade: the
university's OWN indexed corpus first, web second — verdicts grounded in
institutional knowledge. (2) Distrust list: claims echoed only by
known-misinformation domains are marked *tainted* — misinformation sites
asserting a claim counts AGAINST it. (3) Every model call and routing
decision is logged and auditable. No other tool combines these.

**Slide 6 — Powered by AMD.** All inference (claim extraction + verdict
checking) runs on an AMD Instinct GPU: ROCm 7.2 + vLLM 0.16 serving Gemma
as an OpenAI-compatible endpoint, registered as a first-class backend.
Screenshot placeholders: rocm-smi output; the "AMD (ROCm + vLLM)" server in
Drupal config; the decisions log showing calls routed to it. Privacy angle:
student work never leaves institution-controlled AMD hardware.

**Slide 7 — Architecture.** Simple diagram: Drupal node → Content scan →
ai_provider_universal router → [AMD GPU: vLLM/Gemma] for extraction &
verdicts; evidence from Search API vector index (local corpus) with Tavily
web fallback; decisions logged to an audit table exposed as a report.
Everything — models, backends, evidence sources, trust lists — is Drupal
configuration, not code.

**Slide 8 — Not a hackathon toy.** Built as a real open-source module on
drupal.org (ai_provider_universal), on top of the official Drupal AI
ecosystem, direction discussed with the Drupal AI module maintainers.
Months of prior work; the hackathon adds the AMD serving layer and the
university use case.

**Slide 9 — Market.** Universities already budget for content-integrity
tooling (plagiarism detection is a multi-hundred-million-dollar market).
This slots into their existing CMS at module-install cost. Same pipeline
serves newsrooms and government content teams.

**Slide 10 — Ask & links.** GitHub repo URL, live demo URL, drupal.org
project page, demo video link. Closing line: "Claim-level truth, where the
content lives, on AMD."

---

Al terminar: revisá que las URLs reales estén en la slide 10 antes de
exportar el PDF.
