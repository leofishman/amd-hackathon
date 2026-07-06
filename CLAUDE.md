# AMD Hackathon ACT II — estado del proyecto

**Actualizado: 2026-07-06** · Claude mantiene este archivo al día en cada
hito de cada sesión. Leerlo primero al retomar trabajo.

## Decisión de track (2026-07-06)

**Track 3 (Unicorn)**: suite de integridad de contenido sobre
`ai_provider_universal_factcheck` — factcheck claim por claim contra el
corpus propio + AI-writing likelihood + plagio verbatim (Serper) — pitcheada
a universidades ya en Drupal, con inferencia en GPU AMD (ROCm + vLLM).

Track 1 descartado: las reglas publicadas obligan a que toda inferencia pase
por `FIREWORKS_BASE_URL` (local ya no cuenta 0) y el harness es batch Docker
(`/input/tasks.json` → `/output/results.json`), no HTTP/CLI. Queda un agente
mínimo sin usar en `drupal-hackathon/track1-agent/`.

Feedback de Jamie (maintainer Drupal AI): el provider OpenAI-compatible
genérico ya está/estará en AI 2.0 → el titular del pitch es el factchecker,
no el provider.

## Entregables Track 3 (deadline 2026-07-11)

- [x] Runbook del notebook AMD → `track3/NOTEBOOK_RUNBOOK.md` (solo
      inferencia con vLLM, NO fine-tuning; evidencia en `evidence/`)
- [x] Guion del video → `track3/VIDEO_SCRIPT.md` (2:30–3:00, 4 escenas)
- [x] Prompt del deck para Gemini → `track3/GEMINI_DECK_PROMPT.md`
      (Gemini genera el PDF; el pre-screening automático LEE el PDF y el
      repo, NO procesa el video)
- [x] Demo compose funcionando de punta a punta — validado con boot
      prístino (imagen 100% desde drupal.org, sin parches): los 3 claims
      fabricados salen CONTRADICTED con análisis por fuente
- [x] README del repo de submission orientado a jueces (README.md)
- [ ] Leo: correr notebook (4h/día de cuota), grabar video, deck, submit

## Stack demo (este repo)

`docker compose up -d` → Drupal 11 + MariaDB + Ollama (fallback local).
Env vars clave en `.env`: `AMD_VLLM_URL` (endpoint vLLM del notebook, vía
cloudflared), `TAVILY_API_KEY` (evidencia web), `SERPER_API_KEY` (plagio),
`OLLAMA_MODELS`.

Provisioning idempotente en primer boot (`scripts/`): servers (Ollama +
AMD vLLM + Fireworks si hay key) → discovery de modelos → route →
`provision-factcheck.php` (server+índice Search API `evidence_corpus` sobre
articles, settings del factcheck apuntando al modelo AMD si existe) →
`create-demo.php` (corpus "Aldford University" de 4 articles + ensayo con
3 claims fabricados + landing para jueces) → indexación.

## Estado / pendientes técnicos

- Bug del módulo (view `ai_router_log` sin deps rest+serialization):
  arreglado, commit `6389bdb` **ya pusheado por Leo a drupal.org 1.0.x**.
  Workaround igual presente en `setup-hackathon.sh` (habilita
  rest+serialization antes del módulo).
- Drupal 11.4 `standard` instala SIN content types ni body field —
  `create-demo.php` ahora crea article/page + storage/field/displays de
  body a mano (no usar `node_add_body_field()`, falla en 11.4).
- **Demo funcionando end-to-end (2026-07-06)**: con gemma3:4b (1b es
  demasiado débil para extraer claims; default del compose ya es 4b) el
  ensayo da: 1875 → CONTRADICTED con análisis citando 1892, Nobel →
  CONTRADICTED, claims verdaderos → SUPPORTED. Score ~0.375.
- Segundo fix al módulo: `EvidenceRetriever` usaba AND por defecto en
  backends keyword → sin evidencia ante cualquier detalle fabricado.
  Commit `181fed7` (parse mode terms + OR) — **pusheado a drupal.org**.
  Imagen rebuildeada --no-cache con ambos fixes: sin hot-copies.
- **GPU AMD conectada y funcionando (2026-07-06)**: Qwen2.5-7B en vLLM/ROCm
  en el notebook, túnel cloudflared (URL efímera en `.env` como
  AMD_VLLM_URL — cambia en cada sesión del notebook, re-tunelear y
  actualizar). Factcheck corriendo en la GPU con los mejores veredictos
  (todos correctos). Server `amd_vllm` + modelo con costo 0 agregado a la
  ruta `hybrid_chat`; decisions log muestra chosen_model=amd_vllm__qwen...
  Evidencia inicial en `evidence/` (falta: rocm-smi.txt y vllm.log del
  notebook, Leo los tiene en /workspace).
  Gotcha notebook: proxy TLS self-signed → `curl -k`; Gemma es gated en HF
  → usar Qwen. PDF del deck ya generado por Gemini.
- Dos fixes más al módulo probando Qwen en GPU (2026-07-06):
  `85a362e` (extractor traducía claims al chino → prompt pinned al idioma
  del texto) y `2f0d076` (Qwen emite un array JSON por línea → fallback en
  el parser). **Pendientes de push por Leo.** Content scan final: 12 claims,
  los 3 fabricados CONTRADICTED, score 50% — pantalla lista para el video.
- max_claims subido a 12 (live + provisioning).
- Repo GitHub creado: `github.com/leofishman/amd-hackaton` (typo "hackaton"
  en el nombre — cosmético). Commit `60e0a1a` con todo el Track 3 —
  **pendiente `git push` por Leo**. README reescrito para juez LLM
  (tabla "Submission facts" arriba con URLs y camino verificable).
- Deck PDF revisado (está en el repo raíz): 3 fixes pedidos a Leo —
  (1) slide AMD usa capturas de stock FALSAS → reemplazar por reales
  (rocm-smi, server amd_vllm, decisions log), (2) dice "serving Gemma" →
  Qwen2.5-7B, (3) slide final sin URLs literales → agregarlas.
- **Gemma 3 12B corriendo en la GPU AMD (2026-07-06 noche)**: Leo aceptó
  la licencia en HF + token, `pkill` del Qwen viejo (nohup sobrevive al
  cierre de terminal; la GPU quedaba ocupada), Gemma servido en puerto
  8000 (mismo túnel). Factcheck reapuntado a
  `amd_vllm__google_gemma_3_12b_it` — veredictos correctos. El deck ya es
  veraz con "serving Gemma"; quedan 2 fixes del deck (capturas reales +
  URLs finales).
- Video: hay una toma grabada (con ~/grabar.sh, ffmpeg verificado); Leo
  quiere regrabar mejor y montar voz aparte
  (`ffmpeg -i video.mp4 -i voz.wav -map 0:v -map 1:a -c:v copy final.mp4`).
- Punto de pitch agregado: sistemas cerrados (Turnitin) = caros, opacos y
  sin acceso al corpus interno; módulo GPL en el CMS propio = lo contrario.
- OJO: `.env` pisaba OLLAMA_MODELS con gemma3:1b — ya corregido a 4b en
  `.env` y `.env.example`. `docker compose up -d` tras cambiar env RECREA
  el contenedor (pierde hot-copies).
- Gotchas aprendidos: el orden del setup importa (contenido/campos ANTES
  del índice); los veredictos se cachean por claim (drush cr para
  re-evaluar); timeout del server local subido a 120s para CPU.
- Notebook AMD: elegir la imagen "ROCm 7.2 + vLLM 0.16.0 + PyTorch 2.9"
  (no la de Unsloth/llama.cpp, esa es para fine-tuning).
- Validación final de boot limpio corriendo al cierre de sesión
  (2026-07-06): rebuild + install + factcheck test.
- Para resetear el sitio sin re-descargar modelos:
  `docker compose down && docker volume rm amd-hackathon_db_data
  amd-hackathon_drupal_sites && docker compose up -d --build`.

## Referencias

- Estrategia pre-kickoff (parcialmente obsoleta): `STRATEGY.md`
- Módulo: `~/Proyects/ai_provider_universal` (branch 1.0.x, remotes:
  origin=drupal.org, github=leofishman)
- Reglas del hackathon: guía del participante (Google Drive, link en chat)
