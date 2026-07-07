# Video Mixing Plan — Notebook (hoy) + Minisforum AMD local (mañana)

## Objetivo narrativo
Turning the limitation ("I no longer have access to the notebook") into a strength:
- Usar footage del notebook AMD de hoy como evidencia de desarrollo y validación en hardware AMD real.
- Presentar el minisforum como "stack AMD completo corriendo localmente con ROCm + Fireworks para claims complejos".
- Mostrar cascada visible y trusted sites en acción.

## Material disponible hoy (notebook)
- `raw-recording.mkv` (6 min)
- Clips ya cortados en `edits/`, `edits2/`, `edits3/`
  - clip con rocm-smi / vLLM
  - AI routing decisions (chosen_model = amd_vllm...)
  - Content scan del ensayo principal
  - Trusted sites
  - drupal.org page (excelente cierre)

## Qué grabar mañana en minisforum (prioridad alta)
1. rocm-smi del minisforum (mostrar GPU AMD local)
2. Levantar el stack con AMD_VLLM_URL apuntando al Ollama ROCm local
3. Content scan en los **tres ensayos**:
   - Student essay (principal)
   - Everyday life at Aldford (mayoría SUPPORTED)
   - Complex essay (fabricaciones + Darwin)
4. Decisions log mostrando mezcla (amd_local + fireworks cuando escala)
5. Trusted sites list + algún sitio con rep negativa
6. Cierre en https://www.drupal.org/project/ai_provider_universal

Grabar despacio (ver SLOW_RECORDING_PLAN.md).

## Estructura sugerida del video final (~2:00-2:30)

**0:00 – 0:20**  
Notebook rocm-smi + "Desarrollamos y probamos en AMD Instinct real"

**0:20 – 0:55**  
Content scan del ensayo principal (usar clips del notebook de hoy)  
→ 3 claims fabricados → CONTRADICTED

**0:55 – 1:15**  
Corte a minisforum  
rocm-smi local + "Ahora corre el mismo stack en hardware AMD local (Minisforum + ROCm)"

**1:15 – 1:40**  
Scans de los ensayos adicionales + decisions log (mostrar cascada)

**1:40 – 1:55**  
Trusted sites en acción (Nature alta, Wikipedia downgraded)  
+ mención a que reputación afecta veredictos

**1:55 – end**  
Cierre en página del módulo en drupal.org + tagline

## Cómo mezclar técnicamente

Tenés `track3/mix-video.sh` + los condensed ya hechos.

Opciones:

A. Usar los condensed notebook existentes para las primeras partes y grabar clips sueltos mañana para insertar.

B. Crear un nuevo condensed mañana con los nuevos clips y usar ffmpeg para intercalar secciones de los dos condensed.

Ejemplo de comando para insertar una sección de minisforum en medio de un condensed notebook:

```bash
# Supongamos que tenés:
# notebook-part1.mp4 (0-45s del condensed notebook)
# minisforum-middle.mp4 (tus tomas nuevas)
# notebook-part2.mp4

ffmpeg -i notebook-part1.mp4 -i minisforum-middle.mp4 -i notebook-part2.mp4 \
  -filter_complex "[0:v:0][0:a:0][1:v:0][1:a:0][2:v:0][2:a:0]concat=n=3:v=1:a=1[outv][outa]" \
  -map "[outv]" -map "[outa]" \
  -c:v libx264 -crf 19 -c:a aac \
  final-mixed.mp4
```

Después overlay la voz (la misma que usaste antes).

## Actualizaciones que hay que hacer

- Actualizar `track3/VIDEO_SCRIPT.md` con la nueva estructura (notebook → minisforum local).
- Actualizar la landing del demo (ya lo hace `create-enhanced-demo.php`).
- En el deck: cambiar "notebook AMD" por "desarrollado en AMD + corre local en AMD (Minisforum)".
- Guardar evidencia nueva de rocm-smi del minisforum + decisions log con modelo local.

Esto deja el video mucho más fuerte: mostrás que el trabajo se validó en hardware AMD real y que el producto final corre 100% local en hardware AMD del cliente.
