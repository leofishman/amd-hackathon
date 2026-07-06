# Pasos de grabación — URLs exactas (sitio local, admin/admin)

Preparación (antes de grabar):
- Login en http://localhost:8080/user/login
- Zoom del navegador 110–125%
- Pestañas abiertas en este orden (el video es recorrerlas):

## Escena 1 — El problema (0:00–0:25)
**http://localhost:8080/node/5** — el ensayo del estudiante.
Scrollear despacio mientras narrás: "looks fine, three claims are fabricated".

## Escena 2 — El producto (0:25–1:15)
**http://localhost:8080/node/5/factcheck** — la pestaña Content scan.
Correr el scan (o mostrar el resultado ya corrido). Señalar con el mouse:
- "founded in 1875" → CONTRADICTED
- "manuscripts lost" → CONTRADICTED
- "Nobel Prize 1989" → CONTRADICTED
- los SUPPORTED de los claims verdaderos.

## Escena 3 — AMD (1:15–1:50)
1. Terminal del notebook (JupyterLab): `rocm-smi` y
   `grep -i -m3 'rocm\|triton' vllm.log`
2. **http://localhost:8080/admin/config/ai/providers/universal**
   (lista de servers) — mostrar "AMD Instinct GPU (ROCm + vLLM)".
3. **http://localhost:8080/admin/reports/ai-router-decisions** —
   la columna chosen_model = amd_vllm__qwen_qwen2_5_7b_instruct, costo 0.

## Escena 4 — Flexibilidad y cierre (1:50–2:40)
1. **http://localhost:8080/admin/config/ai/providers/universal/factcheck**
   — settings del factcheck: modelos, evidence index, profile.
2. El corpus: **http://localhost:8080/node/1** (mostrar 2 segundos:
   "verdicts grounded in the university's OWN content").
3. Cierre sobre **http://localhost:8080/node/6** (la landing) con el
   nombre del proyecto.

Guion de voz: track3/VIDEO_SCRIPT.md (mismas escenas).
Si el scan tarda en cámara: ya fue corrido antes → el resultado carga
instantáneo; no correrlo en vivo.
