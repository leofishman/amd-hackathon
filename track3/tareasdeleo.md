# Tareas de Leo - 2026-07-07 mañana (Minisforum + AMD inference)

**Objetivo principal**: Hacer que el live demo en https://amd-hackathon.fishman.work corra con **GPU AMD real vía ROCm + Ollama** (o vLLM) para que el decisions log y factcheck muestren "AMD Instinct GPU" con costo 0. Esto es clave para el pitch.

**Plan B**: Fireworks con un modelo denso (Gemma grande) para claims complejos + routing híbrido "barato en AMD / premium en Fireworks".

**Estado actual (de CLAUDE.md)**: Live demo usa gemma3:4b en CPU. Notebook AMD tiene quota de solo 4h/24h. Hay que bajar el Ollama actual en minisforum.

---

## 1. Preparación en Minisforum (mañana temprano)

### 1.1 Detener el Ollama actual (CPU) para liberar recursos/GPU
```bash
# Buscá el contenedor que está corriendo ahora (probablemente el del compose o uno standalone)
docker ps | grep -i ollama

# Paralo (ajustá el nombre si es distinto, ej: ollama o el del compose)
docker stop ollama || docker stop amd-hackathon_ollama_1 || true

# Verificá que el puerto 11434 quede libre
ss -tlnp | grep 11434 || echo "puerto libre"
```

**Importante**: Si tu live demo usa `docker compose` en ~/amd-hackathon-demo, mejor:
```bash
cd ~/amd-hackathon-demo
docker compose stop ollama
```

### 1.2 Verificar que ROCm está disponible
```bash
rocm-smi
rocminfo | head -30
ls -l /dev/kfd /dev/dri
# Deberías ver tu GPU AMD (MI300 o similar) y los devices
```

Si no ves nada o errores, decime y paramos.

### 1.3 Levantar Ollama con ROCm (Docker - la forma que anduvo antes)
Usamos el tag oficial `ollama/ollama:rocm`. A veces es picky con el modelo y el GFX.

```bash
# Limpiar si quedó algo viejo
docker rm -f ollama-rocm 2>/dev/null || true

docker run -d \
  --name ollama-rocm \
  --restart unless-stopped \
  --device /dev/kfd \
  --device /dev/dri \
  --group-add video \
  --ipc=host \
  --shm-size 16g \
  -v ollama-rocm-data:/root/.ollama \
  -p 11435:11434 \
  ollama/ollama:rocm
```

Esperar 10-20s y probar:
```bash
docker logs ollama-rocm --tail 20
curl -s http://localhost:11435/api/tags | head -5
```

### 1.4 Bajar el modelo denso en ROCm (Gemma 3 12B o el que entre)
Gemma es gated en HF a veces. Si falla, usá uno que ya aceptaste antes.

```bash
# Prueba primero con uno que entre en VRAM (mirá rocm-smi mientras carga)
docker exec -it ollama-rocm ollama pull gemma3:12b

# Si da OOM o muy lento, usá el 4b denso:
# docker exec -it ollama-rocm ollama pull gemma3:4b

# Ver modelos cargados
docker exec -it ollama-rocm ollama list
```

**Nota histórica**: A veces hay que setear `HSA_OVERRIDE_GFX_VERSION` o usar una build específica de Ollama con ROCm. Si esto falla, alternativa fuerte es correr **vLLM con ROCm** directamente (ver sección al final).

### 1.5 Probar el endpoint OpenAI-compatible
```bash
curl http://localhost:11435/v1/models
# Debería listar gemma3:12b o lo que bajaste
```

---

## 2. Cablear al Drupal (live demo en minisforum)

### 2.1 Setear AMD_VLLM_URL apuntando al nuevo Ollama ROCm
Esto hace que el provisioning cree el server "AMD Instinct GPU (ROCm + Ollama/vLLM)" y lo prefiera en factcheck (costo 0, aparece en decisions log).

```bash
# En el entorno de tu live demo (puede ser .env del compose o export directo)
export AMD_VLLM_URL=http://localhost:11435/v1

# Si el Drupal corre dentro de Docker y no ve localhost:
# Probá una de estas:
# export AMD_VLLM_URL=http://host.docker.internal:11435/v1
# export AMD_VLLM_URL=http://172.17.0.1:11435/v1   # docker0 bridge
```

Verificá conectividad **desde adentro del contenedor web**:
```bash
# Ajustá según tu compose
docker compose exec web curl -s $AMD_VLLM_URL/models | head -3
```

### 2.2 Re-provision (idempotente)
```bash
# Asegurate de estar en el directorio correcto de tu live demo
# cd ~/amd-hackathon-demo   (o donde esté el código + scripts)

drush scr /hackathon-scripts/provision-servers.php
drush aip:discover-models amd_vllm
drush scr /hackathon-scripts/provision-factcheck.php
drush cr

# Verificá
drush config:get ai_provider_universal_factcheck.settings checker_model
drush config:get ai_provider_universal_factcheck.settings extractor_model
```

Debería elegir el modelo del server `amd_vllm`.

### 2.3 Test rápido
- Andá a un nodo de ensayo → Content scan.
- Mirá `/admin/reports/ai-router-decisions` → chosen_model debe ser del amd_vllm (o el que descubrió).
- rocm-smi durante el scan para confirmar que usa GPU.

---

## 3. Script "más vendedor" + Fireworks con Gemma denso para claims complejos

Idea: Mostrar **tiered inference** (barato/rápido en tu AMD local + premium en Fireworks para claims difíciles).

### 3.1 Activar Fireworks
```bash
export FIREWORKS_API_KEY=tu-key-aqui
```

Re-descubrir:
```bash
drush aip:discover-models fireworks
```

### 3.2 Configurar hybrid (manual o vía script)

Actualmente factcheck usa el mismo modelo para extractor y checker.

**Opción rápida (UI o drush)**:
- Dejá el `amd_vllm__gemma...` como extractor (barato, para extraer claims).
- Para claims complejos usá un modelo denso en Fireworks como checker.

Ejemplo drush (ajustá los IDs exactos después de discover):
```bash
# Ejemplo (reemplazá con los IDs reales que te dé discover-models)
drush config:set ai_provider_universal_factcheck.settings extractor_model 'amd_vllm__gemma3_12b' --yes
drush config:set ai_provider_universal_factcheck.settings checker_model 'fireworks__google_gemma_3_27b_it' --yes   # o el denso que tengas
drush cr
```

### 3.3 Script "vendedor" que propongo (preparado para mañana)

Voy a crear `scripts/setup-hybrid-vendedor.sh` que:
- Asegura servers AMD + Fireworks.
- Configura factcheck con extractor en AMD (rápido/barato) + checker en Fireworks denso.
- Actualiza la ruta híbrida.
- Imprime un resumen lindo para demo ("Simple claims on your AMD GPU — complex verification on premium model").

Correló después del provisioning básico.

---

## 4. Si ROCm Ollama sigue complicado → Alternativas

### 4.1 Vulkan (más fácil a veces en AMD consumer pero menos óptimo)
Ollama tiene soporte experimental para Vulkan.
```bash
# Variante (prueba si ROCm falla)
docker run -d --name ollama-vulkan ... ollama/ollama   # con flags de vulkan si aplica
```
Generalmente ROCm da mejor perf en GPUs AMD server. Probamos primero ROCm.

### 4.2 vLLM directo con ROCm (como en el notebook)
Más confiable para Gemma grande:
```bash
docker run --rm --device=/dev/kfd --device=/dev/dri ... \
  vllm/vllm-openai:latest-rocm \
  --model google/gemma-3-12b-it --port 8000
```
Luego apuntá AMD_VLLM_URL al puerto 8000/v1. Es lo que usábamos en el notebook.

---

## 5. Generar más contenido de demo (para mostrar cascada + trusted sites)

Después de tener el stack AMD local andando:

```bash
# Ejecutar el contenido básico primero si no lo hiciste
drush scr /hackathon-scripts/create-demo.php

# Luego el contenido mejorado (más ensayos + explicación de cascada)
drush scr /hackathon-scripts/create-enhanced-demo.php

# Re-indexar evidencia
drush search-api:index evidence_corpus
drush cr
```

Esto agrega:
- Ensayo "Everyday life" (mayoría SUPPORTED → mayormente AMD local)
- Ensayo "Complex" (múltiples fabricaciones + Darwin → más trabajo de verificación)
- Artículos extra de corpus
- Landing actualizada que explica la cascada AMD local → Fireworks + impacto de trusted sites

Coré varios Content scans y mostrá el decisions log con mezcla de modelos.

## 6. Video: mezclar notebook (hoy) + minisforum (mañana) + blurring

**Blurring del panel (taskbar):**
Los videos de diferentes días tendrán distinta fecha, hora y aplicaciones abiertas en el panel inferior (Cinnamon).
Usá el script incluido:

```bash
cd track3
bash blur-taskbar.sh final.mp4 final-blurred.mp4
# o sobre clips individuales antes de mezclar
```

El script aplica un blur fuerte + overlay negro semitransparente en los ~70px inferiores (ajustable dentro del script). Si el resultado no es perfecto o muy complicado, dejalo sin blur — no es crítico.

**Mezcla general (ver VIDEO_MIXING_PLAN.md para detalles):**
- Usar footage del notebook (hoy) para demostrar "AMD real" (rocm-smi, decisions log amd_vllm).
- Usar footage del minisforum (mañana) para demostrar "stack local AMD + Fireworks".
- Estructura recomendada: notebook proof → minisforum local → cascada + trusted sites.

**Narrative strength**:
- Usar footage del notebook AMD de hoy como "desarrollamos y validamos en hardware AMD real (notebook)".
- Mostrar minisforum como "ahora corre el stack completo localmente en AMD con ROCm + Fireworks para claims complejos".

### Plan de mezcla recomendado

1. **Hoy (notebook)** — ya tenés:
   - raw-recording.mkv y los clips en `edits/`, `edits2/`, `edits3/`
   - Escenas fuertes: rocm-smi, vLLM log, decisions log con `amd_vllm__...`, Content scan del ensayo principal, drupal.org page.

2. **Mañana (minisforum)** grabá (usando SLOW_RECORDING_PLAN.md):
   - rocm-smi del minisforum (mostrar GPU AMD local)
   - Levantar el stack con AMD_VLLM_URL apuntando al ROCm Ollama
   - Correr Content scan en los 3 ensayos (main + Everyday + Complex)
   - Mostrar decisions log con mezcla (amd_ local + fireworks cuando escala)
   - Lista de Trusted sites (Nature alta rep, Wikipedia downgraded)
   - Final en la página de drupal.org del módulo

3. **Edición (después de grabar mañana)**:
   - Usá los clips existentes del notebook para las partes de "prueba en AMD real".
   - Insertá tomas del minisforum para "producción en hardware local".
   - En el momento del decisions log: mostrá primero notebook, luego corte a minisforum con el mismo log (o log mezclado).
   - Cuando expliques cascada: mostrá una scan simple (AMD local) + una compleja (aparece Fireworks).
   - Mantener el cierre con drupal.org.

Podés reutilizar `track3/mix-video.sh` o armar un ffmpeg más elaborado combinando los condensed de hoy con nuevos clips de mañana.

Ejemplo de estructura de video final:
- 0-15s: Notebook rocm-smi + "AMD Instinct"
- 15-50s: Content scan del ensayo principal (notebook footage)
- 50-70s: Corte a minisforum rocm-smi + decisions log local
- 70-100s: Scans de los ensayos extra + trusted sites en acción
- 100-115s: Log mostrando cascada AMD → Fireworks
- Cierre: drupal.org page + tagline

Actualizá el guion en `track3/VIDEO_SCRIPT.md` con esta narrativa de "desarrollo en notebook → producción en minisforum local".

## 7. Checklist mañana (copia-pega)

1. [ ] Parar Ollama viejo.
2. [ ] Levantar ollama-rocm + bajar gemma denso.
3. [ ] Configurar AMD_VLLM_URL + FIREWORKS_API_KEY.
4. [ ] Re-provision + correr `create-enhanced-demo.php`.
5. [ ] Verificar cascada en decisions log.
6. [ ] Grabar nuevo material en minisforum (múltiples ensayos + rocm-smi local + trusted sites + Fireworks calls).
7. [ ] Editar video mezclando footage notebook (hoy) + minisforum (mañana).
8. [ ] Actualizar landing/demo text, README y CLAUDE.md.
9. [ ] Test completo del live demo.
10. [ ] Push + submit.

---

## Notas adicionales

- El video ya tiene tomas del notebook AMD. Para el live usamos minisforum.
- Si el modelo grande no entra, usá gemma3:4b en ROCm igualmente (mejor que CPU).
- Guardá capturas: rocm-smi durante inferencia, decisions log, Content scan.
- Si todo falla con ROCm, Fireworks + "corre en cualquier AMD (Ryzen AI incluido)" sigue siendo vendible (ver CLAUDE notas).

**Si algo explota mañana, mandame el error exacto (rocm-smi + docker logs + curl del endpoint).**

Listo para que te pongas temprano. ¡Suerte con el submit!
