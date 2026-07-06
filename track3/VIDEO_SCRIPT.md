# Guion del demo video (2:30–3:00, screen recording + voz)

Regla de oro: los primeros 20 segundos deciden si el juez sigue mirando.
Empezar por el problema, no por Drupal.

## Escena 1 — El problema (0:00–0:25)
Pantalla: un nodo Drupal "ensayo de estudiante" con 2-3 afirmaciones falsas
plausibles mezcladas entre hechos reales.

> "Universities already pay fortunes for plagiarism detection. But plagiarism
> tools can't tell you if a claim is *false* — only if it's copied. This
> essay looks fine. Three of its claims are fabricated."

## Escena 2 — El producto (0:25–1:15)
Pantalla: click en la pestaña **Content scan** del nodo. Mostrar el resultado:
claims extraídos uno por uno, veredicto por claim (SUPPORTED / UNSUPPORTED /
CONTRADICTED), score final.

> "This is a content-integrity suite running natively inside Drupal — the
> CMS these universities already run. One click runs three checks: a
> factcheck that extracts every claim and verifies it against the
> university's *own* indexed corpus, an AI-writing likelihood score, and a
> verbatim plagiarism search. Plagiarism tools stop at 'is it copied?' —
> this also answers 'is it true?' and 'who wrote it?'. No copy-pasting
> into ChatGPT. No new platform to buy."

Señalar con el mouse un claim CONTRADICTED y su evidencia.

## Escena 3 — AMD compute (1:15–1:50)
Pantalla dividida o corte: terminal del notebook con `rocm-smi` y el log de
vLLM; luego el server "AMD MI300 (ROCm + vLLM)" en la config de Drupal; luego
`/admin/reports/ai-router-decisions` mostrando las llamadas a ese server.

> "Every inference call — claim extraction and verdict checking — runs on an
> AMD Instinct GPU with ROCm and vLLM, served as an OpenAI-compatible
> endpoint. Here's the decision log: every call, every model, every routing
> decision, audited. Sensitive student work never has to leave
> university-controlled AMD hardware."

## Escena 4 — Flexibilidad y cierre (1:50–2:40)
Pantalla: form de configuración del factcheck (modelos, evidence index,
trusted/distrusted domains) y la lista de routes.

> "Everything is configuration, not code: which models, which evidence
> sources, which domains to trust — and which domains count *against* a
> claim when they echo it. It's GPL, published on drupal.org, built on the
> Drupal AI ecosystem. For any university already on Drupal, this is a
> module install away."

Cierre, pantalla con el nombre del proyecto + URL del repo:

> "Drupal AI Factchecker — claim-level verification, on your corpus, on AMD."

## Checklist de grabación
- [ ] Resolución 1080p mínimo, zoom del browser 110–125% (texto legible).
- [ ] Contenido demo cargado ANTES de grabar (nada de crear nodos en vivo).
- [ ] El Content scan ya corrido una vez (cache caliente, sin espera muerta).
- [ ] Si la latencia del scan es larga, cortar la espera en edición.
- [ ] Audio: micrófono cerca, sin ventilador de fondo; regrabar la voz sobre
      el video si hace falta.
