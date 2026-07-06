# Prompt para Gemini — slide deck Track 3, v2 (exportar a PDF)

Pegá esto en Gemini tal cual. Después exportá a PDF: el pre-screening
automático LEE el PDF, así que el texto de las slides importa más que el
diseño — nada de texto importante dentro de imágenes.

⚠️ Donde diga [SCREENSHOT: ...] insertá una captura REAL tuya — nunca
imágenes de stock. El pre-screening verifica el uso de AMD; una captura
falsa en esa slide puede leerse como evidencia fabricada.

---

Create a 10-slide pitch deck for a hackathon submission (AMD Developer
Hackathon, Track 3: Open Innovation). Clean, professional, dark theme.
All content in English. One idea per slide, minimal text, no filler.

**Slide 1 — Title.** "Drupal AI Factchecker — claim-level verification on
your own corpus, powered by AMD." Team: Drupal AI Router (Leo Fishman).
GPL-2.0+, published on drupal.org.

**Slide 2 — The problem.** Universities pay heavily for plagiarism
detection (a $1B+ market), but plagiarism tools detect *copying*, not
*falsehood*. AI-generated essays are original text full of fabricated
claims — invisible to every plagiarism scanner. Editors and educators have
no in-workflow tool to verify factual accuracy.

**Slide 3 — The insight.** The institutions with this problem already run
Drupal (it powers a large share of .edu and .gov sites). The verification
tool should live where the content lives — not in another SaaS tab, and
not in a closed system that can't see the institution's own knowledge.

**Slide 4 — The product: one click, three checks.** On any content node,
the "Content scan" tab runs: (1) Factcheck — atomic claim extraction →
evidence retrieval → per-claim verdict (SUPPORTED / UNSUPPORTED /
CONTRADICTED) with auditable score; (2) AI-writing likelihood — 0-100
score, honestly framed as a hint, not proof; (3) Verbatim plagiarism —
the text's most distinctive sentences searched as exact phrases on the
web. [SCREENSHOT: Content scan del ensayo — los 3 CONTRADICTED + el hit
de plagio de la frase de Darwin con su URL de fuente]

**Slide 5 — Trust is YOUR editorial decision.** Verdicts are grounded in
the institution's own indexed corpus first, curated web sources second.
Each source is a content entity with an owner-assigned reputation backed
by citable external assessments (NewsGuard, MBFC, peer-review status) —
and known-misinformation domains get negative reputation: claims echoed
only by them are marked *tainted* and count AGAINST the score. The
software has no opinion; your institution does, and the audit trail shows
it. Source curation installs with a Drupal recipe. [SCREENSHOT: lista de
Trusted sites — Nature rep 9 (peer-reviewed) junto a Wikipedia rep 2
(decisión editorial fechada) y el dominio distrusted rep -8]

**Slide 6 — Powered by AMD Instinct.** All inference — claim extraction
and verdict checking — runs on an AMD Instinct GPU: ROCm 7.2 + vLLM 0.16
serving Google's Gemma 3 12B as an OpenAI-compatible endpoint, registered
as a first-class backend in Drupal. Student work never leaves
institution-controlled AMD hardware. [SCREENSHOTS REALES, los tres:
(a) rocm-smi en la terminal del notebook, (b) el server "AMD Instinct GPU
(ROCm + vLLM)" en la config de Drupal, (c) el decisions log mostrando
chosen_model = amd_vllm__google_gemma_3_12b_it]

**Slide 7 — Architecture.** Flow: Drupal node → Content scan →
ai_provider_universal router → AMD GPU (vLLM / Gemma) for extraction &
verdicts → evidence from a Search API index over the local corpus
(keyword or vector) with Tavily web fallback → every decision logged to
an auditable report. Everything — models, backends, evidence sources,
trust lists — is Drupal configuration, not code.

**Slide 8 — Real, not a hackathon toy.** Built as a production open-source
module on drupal.org (ai_provider_universal), on the official Drupal AI
ecosystem, direction discussed with the Drupal AI maintainers. Months of
prior work; the hackathon added the AMD serving layer, the university use
case — and battle-testing: three robustness fixes were found and shipped
upstream during the hackathon by running real models on AMD hardware.

**Slide 9 — Market.** Plagiarism detection alone is a $1B+ market that
universities already budget for — yet incumbents are closed systems: they
can't index your corpus, can't explain their verdicts, and impose their
vendor's trust decisions. This slots into the CMS they already run at
module-install cost. Same pipeline serves newsrooms and government
content teams.

**Slide 10 — Links (texto plano, URLs literales).**
- GitHub: https://github.com/leofishman/amd-hackathon
- Module: https://www.drupal.org/project/ai_provider_universal
- Demo video: [URL del video]
- Live demo: [URL si la hay]
Closing line: "Claim-level truth, where the content lives, on AMD."

---

Checklist antes de exportar:
- [ ] Las 5 capturas son reales (nada de stock).
- [ ] URLs literales y correctas en la slide 10.
- [ ] Dice Gemma 3 12B (es lo que corre hoy en la GPU).
- [ ] Sin slides de "Image Sources" con stock — si todas las imágenes son
      capturas propias, no hacen falta créditos.
