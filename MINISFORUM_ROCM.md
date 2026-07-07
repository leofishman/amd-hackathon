# Running Gemma on Minisforum with ROCm + Ollama (AMD GPU)

## Why this is important
The notebook AMD has quota (4h/24). For reliable live demo + pitch ("runs on AMD Instinct GPU with ROCm"), run the model on your minisforum.

The `docker-compose.yml` now includes a proper ROCm Ollama service. After killing external memory-hogging processes (vLLM, llama.cpp), just use `docker compose`.

Ollama + ROCm exposes an OpenAI-compatible endpoint, so the existing `amd_vllm` server + factcheck logic works with almost no changes.

## 1. On minisforum: verify ROCm

```bash
rocm-smi
rocminfo | head -20
ls /dev/kfd /dev/dri
```

You should see your AMD GPU and the devices.

## 2. Run Ollama with ROCm via docker compose (recommended)

The `docker-compose.yml` now has a proper `ollama` service using `ollama/ollama:rocm` with all the required device mappings, ipc, shm, etc.

**On minisforum:**

1. First kill the memory-hogging processes (vLLM, standalone llama.cpp, old ollama instances):

```bash
pkill -f vllm || true
pkill -f ollama || true
pkill -f 'python.*llama' || true
free -h
rocm-smi
```

2. Make sure your `.env` has the right models and (optionally) AMD_VLLM_URL:

```bash
# .env
OLLAMA_MODELS=gemma3:4b
AMD_VLLM_URL=http://172.17.0.1:11435/v1
```

3. Start (or restart) the stack:

```bash
docker compose up -d ollama
# or full stack:
# docker compose up -d
```

4. Pull model (it will now use ROCm):

```bash
docker compose exec ollama ollama pull gemma3:4b
# or gemma3:12b if it fits
```

5. Test:

```bash
curl http://localhost:11435/v1/models
```

You should see the model listed. Watch `rocm-smi` in another terminal while it loads.

## 3. Wire it into the Drupal instance (on the same minisforum)

The compose already exposes the ROCm ollama on host port 11435.

Set in your `.env` (or export):

```bash
AMD_VLLM_URL=http://172.17.0.1:11435/v1
```

Verify from inside the web container:

```bash
docker compose exec web curl -s $AMD_VLLM_URL/models | head -3
```

Then re-provision the servers and factcheck:

```bash
drush scr /hackathon-scripts/provision-servers.php
drush aip:discover-models amd_vllm
drush scr /hackathon-scripts/provision-factcheck.php
drush cr
```

Check:
- `/admin/reports/ai-router-decisions` → should show the AMD-labeled server.
- Content scan on the demo essay → should use the ROCm model (cost 0).

## 4. Fireworks as reliable backup

If ROCm is unstable or you run out of time:

```bash
export FIREWORKS_API_KEY=your_key_here
```

The provisioning already creates the `fireworks` server when the key is present. The route will still prefer the cheap local/AMD one.

## 5. In this repo (for future boots)

On the minisforum, put this in your `.env` next to `docker-compose.yml`:

```
AMD_VLLM_URL=http://172.17.0.1:11435/v1
OLLAMA_MODELS=gemma3:4b
```

Then just:

```bash
docker compose up -d
```

The ollama service is now the ROCm one.

## Notes / Gotchas

- On the Ryzen AI 9 HX 370 (890M iGPU) start with **gemma3:4b**. The 12B may be tight.
- Always kill external vLLM / old Ollama / llama.cpp processes first (they hold unified memory).
- Watch `rocm-smi` while models load and during Content scans.
- Ollama ROCm image uses the host's ROCm libraries.
- In decisions log you will see the model id coming from the Ollama server (e.g. `amd_vllm__gemma3_4b`) with cost 0.

This gives you a persistent AMD GPU story without depending on the notebook quota.
