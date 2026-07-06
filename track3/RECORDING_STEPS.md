# Pasos de grabación v2 — URLs exactas (sitio local, admin/admin)

El video se graba MUDO con `~/grabar.sh` (Ctrl+C corta); la voz la genera
Gemini con `track3/VOICEOVER.txt` y se monta después:
`ffmpeg -i pantalla.mp4 -i voz.mp3 -map 0:v -map 1:a -c:v copy -shortest final.mp4`

Las escenas siguen el orden del voiceover — navegá lento (cada pantalla
3-6 segundos quieta) y sobrará video para cortar, mejor que faltar.

## Preparación (antes de grabar)
- Login en http://localhost:8080/user/login (admin/admin)
- Zoom del navegador 110–125%
- El Content scan del ensayo ya corrido una vez (resultado en cache)
- Notebook AMD vivo con Gemma servido (para la terminal de la escena 3)
- Pestañas abiertas en el orden de abajo

## Escena 1 — El problema (~0:00–0:25)
1. **http://localhost:8080/node/5** — el ensayo del estudiante.
   Scroll lento de punta a punta. El texto se ve impecable — eso es lo
   que el voiceover está diciendo ("looks perfectly fine").

## Escena 2 — Un click, tres chequeos (~0:25–1:15)
2. **http://localhost:8080/node/5/factcheck** — el Content scan completo:
   - Pausa sobre los tres **CONTRADICTED** (1875, manuscritos, Nobel) —
     señalalos con el mouse siguiendo el orden del voiceover.
   - Pausa sobre un par de SUPPORTED.
   - Scroll a la sección **AI likelihood** (score + rationale).
   - Scroll a **Plagiarism**: la frase de Darwin con URL de fuente.
     Quedate 4-5 segundos — es el golpe visual del video.

## Escena 3 — AMD (~1:15–1:50)
3. Terminal del notebook (JupyterLab), ya con esto corrido y visible:
   ```
   rocm-smi
   grep -i -m3 'rocm\|triton' vllm-gemma.log
   ```
   (la VRAM cargada ~30GB con Gemma se ve mucho mejor que idle)
4. **http://localhost:8080/admin/config/ai/providers/universal** —
   mostrar el server **"AMD Instinct GPU (ROCm + vLLM)"**.
5. **http://localhost:8080/admin/reports/ai-router-decisions** —
   la columna chosen_model = `amd_vllm__google_gemma_3_12b_it`, costo 0.

## Escena 4 — Todo es configuración (~1:50–2:40)
6. **http://localhost:8080/admin/config/ai/providers/universal/factcheck**
   — settings del factcheck: modelos, evidence index, profile, keys.
7. **Evidence index**: http://localhost:8080/admin/config/search/search-api
   → click en **Evidence corpus** — mostrar que la evidencia es un índice
   Search API estándar sobre el contenido propio (4 articles indexados).
   Toma corta: 4 segundos.
8. **Trusted sites**: http://localhost:8080/admin/content?type=trusted_site
   — la lista completa. Abrí **Nature (rep 9)** y mostrá los assessments
   (peer-review); volvé y abrí **Wikipedia (rep 2)** con su decisión
   editorial fechada. Si entra, mostrar también el distrusted
   `fakenews.example` (rep -8). Este contraste es la slide 5 del deck
   en movimiento.
9. Un artículo del corpus 2 segundos (**/node/1**) — "verdicts grounded
   in the university's OWN content".

## Cierre (~2:40–2:55)
10. **http://localhost:8080/node/6** — la landing del demo.
11. **https://www.drupal.org/project/ai_provider_universal** — la página
    del módulo en drupal.org (código fuente + factcheck). Ideal para el
    cierre del voiceover ("published on drupal dot org as the Universal
    AI Provider module. Here is the full source...").

Actualizar el VOICEOVER.txt cuando se agregue esta pantalla.

## Checklist técnico
- [ ] 1080p mínimo; texto legible en pausa.
- [ ] Nada de crear contenido en vivo — todo pre-cargado.
- [ ] Si una pantalla tarda, cortala en edición (el -shortest de ffmpeg
      ajusta al final, los cortes intermedios hacelos antes de montar).
- [ ] Duración objetivo del video mudo: ~3:00 (el voiceover dura ~1:50-2:10
      leído por TTS; sobra margen para estirar tomas en el montaje).

## Post-procesado (sync de voz)

El recording crudo dura ~6 min porque se navegó despacio. Para que coincida
con el voiceover (~2 min) + mostrar la página de drupal.org al final:

1. Generá el audio:
   - Copiá `VOICEOVER.txt` (actualizado con mención explícita a la página del
     módulo) a Gemini / ElevenLabs / tu TTS favorito.
   - Exportá como `voiceover.mp3` o `voiceover.wav` (incluí las pausas).
   - Guardalo en `track3/`.

2. Usá el video condensado ya editado (recomendado):
   ```bash
   ffmpeg -i condensed-screen.mp4 -i voiceover.mp3 \
     -map 0:v -map 1:a \
     -c:v libx264 -preset fast -crf 19 \
     -c:a aac -b:a 160k \
     -shortest -movflags +faststart \
     final-demo.mp4
   ```

   Esto usa las tomas clave (essay → claims → AMD decisions → config/trusted
   sites → artículo propio → drupal.org) ya recortadas a ~2:28. El -shortest
   ajusta al final del audio.

3. Si querés usar TODO el raw (lento):
   - Primero generá screen-full-clean.mp4 (sin audio) si no está.
   - Luego el mismo comando pero con `screen-full-clean.mp4`.
   - El video se cortará temprano (probablemente antes de drupal.org).

4. Verificación:
   - `ffprobe final-demo.mp4` (duración ~2:00-2:30)
   - Abrí el mp4 y chequeá que el drupal.org aparezca cerca del cierre.
   - Si el timing de una escena está off, ajustá los -ss/-t de los clips en
     `edits/` y re-concatená (ver `edits/concat.txt`).

Archivos generados:
- raw-recording.mkv (original)
- condensed-screen.mp4 (recomendado, ya editado)
- screen-full-clean.mp4 (full sin audio)
- edits/ (clips intermedios por si necesitás re-armar)
- VOICEOVER.txt (actualizado 2026-07-06 con la página del módulo)

