# Track 3 — Runbook del notebook AMD (solo inferencia, no fine-tuning)

Objetivo: que el factcheck de `ai_provider_universal` corra su extractor y
checker contra un endpoint vLLM servido por la GPU AMD del notebook. Eso es
la evidencia de "AMD compute usage" que exige el pre-screening.

## 1. En el notebook (ROCm 7.2 + vLLM 0.16)

```bash
# Evidencia de hardware AMD — capturá la salida (screenshot + texto al repo)
rocm-smi
python -c "import torch; print(torch.cuda.get_device_name(0))"

# Servir un modelo. Elegí según VRAM disponible (rocm-smi te la muestra):
#   holgado : google/gemma-3-27b-it (~60GB en bf16 — solo si la GPU es MI300-class)
#   seguro  : google/gemma-3-12b-it
#   mínimo  : google/gemma-3-4b-it (alcanza de sobra para extractor/checker)
vllm serve google/gemma-3-12b-it --max-model-len 8192 --port 8000
```

Guardá el log de arranque de vLLM: muestra el device ROCm/HIP → evidencia AMD.

## 2. Exponer el endpoint hacia tu Drupal local

Si la plataforma del notebook da URL pública para el puerto 8000, usala.
Si no, túnel rápido sin cuenta:

```bash
cloudflared tunnel --url http://localhost:8000
# imprime https://<algo>.trycloudflare.com → esa es tu base URL
```

Verificá desde tu máquina:

```bash
curl -s https://<tunel>/v1/models
```

## 3. En Drupal (una vez)

1. `/admin/config/ai/universal/servers` → Add server:
   - Base URL: `https://<tunel>/v1`
   - Sin API key (o dummy si el form la exige)
   - Nombre: **AMD MI300 (ROCm + vLLM)** — este nombre aparece en el log de
     decisiones y en el Content scan → evidencia AMD visible en la demo.
2. Correr el descubrimiento de modelos del server.
3. Settings del factcheck: extractor model y checker model → el modelo del
   server AMD.
4. Probar: Content scan en un nodo → verificar en
   `/admin/reports/ai-router-decisions` que las llamadas salieron al server AMD.

## 4. Evidencia a guardar en el repo (para el pre-screening automático)

- `evidence/rocm-smi.txt` + screenshot
- `evidence/vllm-startup.log` (recortado al banner con el device HIP)
- Screenshot del server "AMD MI300 (ROCm + vLLM)" configurado en Drupal
- Screenshot del decisions log mostrando llamadas al server AMD

El pre-screening lee repo + PDF + URL de demo. El video no lo procesa la
máquina — es para los jueces humanos.
