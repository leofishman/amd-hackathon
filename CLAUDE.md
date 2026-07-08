# AMD Hackathon ACT II — estado del proyecto

**Actualizado: 2026-07-06 (noche)** · Claude mantiene este archivo al día
en cada hito de cada sesión. Leerlo primero al retomar trabajo.

## ESTADO: listo para submit (deadline 2026-07-11, Leo apunta a 07-07)

- **Video final**: `track3/final-piper.mp4` (1:47, cierra en drupalcode
  mostrando factcheck.md — calza con el voiceover). Alternativa de voz:
  montar audio de final-demo-improved sobre video piper. Subir a YouTube
  (título/descripción ya entregados en el chat).
- **Live demo**: https://amd-hackathon.fishman.work (MiniSforum,
  ~/amd-hackathon-demo, puerto 8088, túnel cloudflared local — ruta en
  ~/.cloudflared/config.yml). Usuario `judge` con permisos mínimos.
  Passwords en scratchpad/demo-pass.txt de la sesión del 06-07 → Leo debe
  guardarlos en su gestor. gemma3:4b local (CPU); factcheck verificado.
- **Repo**: renombrado a `amd-hackathon` (typo arreglado). Commits
  `60e0a1a` + `9968974` listos — **falta `git push`** (Leo).
- **Módulo**: 4 commits pendientes de push a drupal.org 1.0.x (idioma,
  parser línea-por-línea, assessments seed, docs de modelos verificados).
- **Pendientes Leo (mañana)**: push ambos repos; PDF v2 del deck (prompt
  en track3/GEMINI_DECK_PROMPT.md + capturas reales — usar frames del
  video); keys Tavily/Serper por UI en el live demo; subir video; submit
  en lablab con: repo + PDF + video + URL live demo + credenciales judge.

## Estado sesión 07-07 (tarde) — demo listo para grabar

- **Live demo sano y validado end-to-end** (scan UI completo sin 524, por
  la URL pública). Modelos: extractor/detector `gemma4:e2b`, checker
  `gemma4:e4b` (tier 5), TODO local en ROCm (ollama:rocm del compose).
  gemma4 12b también bajado y registrado (tier 4). El factcheck usa
  modelos FIJOS a propósito (no route): la extracción vía route se iba a
  Fireworks y moría por el proxy.
- **Fix 524 (Cloudflare corta a ~100s)**: `batchVerifyClaims` ahora
  multi-pass, tandas de 3 claims por request. Commit `46c9388` en el
  módulo + fix del sort de la view decisions (DESC de verdad). PUSHEADO a
  drupal.org e imagen rebuildeada en minisforum. OJO: una tanda llegó a
  120s (raspando) — decisión pendiente: chunk 3→2.
- **MBFC completo importado**: ~7.850 trusted sites con reputación
  computada + `field_bias` normalizado (left/lean_left/center/...). Fix a
  los 2 scripts de Grok: labels "Very High"/"Least Biased" no matcheaban
  (Reuters quedaba distrusted) y no seteaban `field_bias` (coverage/
  blindspot salía vacío). 896 nodos basura borrados. nytimes.com tiene
  "Site editorial assessment" con refs de Leo. SIN COMMITEAR en el repo.
- **Fireworks**: server + key entity (env). kimi-k2p6 (tier 5) y
  gpt-oss-120b (tier 4) con costos. Ruta hybrid_chat: gemma3:4b + e2b +
  e4b locales + kimi premium (amd_vllm sacados: túnel muerto).
- **BUG ZOMBIE (post-video investigar)**: un worker apache (PID 34) quedó
  loopeando tras un 524: manda conversación creciente (67k tokens) por
  hybrid_chat → kimi, ~$0.04/min. Se mata con kill del worker (o el
  apagado del server de hoy). Causa raíz probable en el flujo
  route+verifier tras aborts del cliente — falta guard connection_aborted
  o límite de iteraciones. Gastó ~$1-2 de crédito.
- **Assets video listos**: guion actualizado (track3/VIDEO_SCRIPT.md),
  deck prompt actualizado (GEMINI_DECK_PROMPT.md), landing node/6
  reescrita, evidencia fresca en evidence/ (minisforum-rocm-smi.txt con
  GPU 100% + ollama-rocm.log). BORRAR `evidence/cloudflared` (37MB) antes
  del push. Escena tmux: amdgpu_top + lscpu durante scan.
- **Pendientes**: commit+push repo demo (scripts, guion, deck, evidencia,
  Dockerfile con max_execution_time=300, create-demo.php nueva landing);
  mail a MBFC redactado en el chat (editor@mediabiasfactcheck.com), Leo
  lo manda con el video; ~/amd-hackathon-demo en minisforum se sincroniza
  con rsync — EXCLUIR `.env` (hoy lo pisé y volteé el sitio: WEB_PORT y
  keys; Leo ya lo restauró).
- Ollama del compose en minisforum quedó como servicio `ollama` (Grok lo
  había sacado a un contenedor suelto + hosts hack — revertido). Modelos
  en volumen amd-hackathon-demo_ollama_data. Al recrear web: /data
  (JSONs MBFC) se pierde, re-copiar con docker cp si se re-corre el sync.

## Notas para la sesión del 07-07 (mañana, parcialmente obsoletas)

- **Video v2 (Leo quiere regrabar con consejos de Grok)**: reusar la
  escena de la notebook Instinct del material YA grabado
  (track3/edits/ + raw-recording.mkv) — es la evidencia AMD fuerte.
  Sumar toma de 5s del MiniSforum (`lscpu | grep "Model name"` →
  "AMD Ryzen AI 9 HX 370 w/ Radeon 890M"). Grabar contra el live demo
  público (URL real en la barra) en vez de localhost. Voiceovers TTS en
  ~/voiceover-*.mp3; montaje: track3/mix-video.sh.
- **Notebook**: cuota 4h se renueva cada 24h; licencia Gemma en HF ya
  aceptada; runbook 15 min. Si no levanta, el plan B Ryzen AI ya está en
  el README ("Runs on anything AMD"). NO usar Fireworks como claim AMD
  (hardware no demostrable).
- **Tag `1.0.0-alpha2`**: creado SOLO local en el módulo (error mío:
  prematuro). Si mañana hay más commits, re-apuntarlo:
  `git tag -d 1.0.0-alpha2 && git tag -a 1.0.0-alpha2` en el commit
  final, recién ahí pushear tag + crear release node en drupal.org.
- **Leo quiere poner Wikipedia y Reuters en negativo en el live demo**
  (decisión editorial suya, la hace él por UI). Avisado: negativo =
  distrust activo (claims echoed → tainted, -0.5 c/u), no "poco
  confiable"; sugerido rep 1-2 con assessment citado para no confundir
  jueces, pero es su call.
- **Visión post-hackathon**: Leo ve producto vendible (universidades ya
  en Drupal que pagan Turnitin). Bajas expectativas del hackathon en sí;
  el producto queda igual. Posible continuación comercial.
- Passwords del live demo: Leo los tiene en ~/demo-pass.txt (debe
  moverlos a su gestor y borrar el archivo).

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
