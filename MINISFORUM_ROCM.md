# Running Gemma on Minisforum with ROCm + Ollama (AMD GPU)

## Why this is important
The notebook AMD has quota (4h/24). For reliable live demo + pitch ("runs on AMD Instinct GPU with ROCm"), run the model on your minisforum if it has a compatible AMD GPU.

Ollama + ROCm works well and exposes an OpenAI-compatible endpoint, so the existing `amd_vllm` server + factcheck logic works with almost no changes.

## 1. On minisforum: verify ROCm

```bash
rocm-smi
rocminfo | head -20
ls /dev/kfd /dev/dri
```

You should see your AMD GPU and the devices.

## 2. Run Ollama with ROCm (recommended on a separate port)

```bash
docker run -d \
  --name ollama-rocm \
  --restart unless-stopped \
  --device=/dev/kfd \
  --device=/dev/dri \
  --group-add video \
  --ipc=host \
  --shm-size 16g \
  -v ollama-rocm:/root/.ollama \
  -p 11435:11434 \
  ollama/ollama:rocm
```

Wait a bit, then pull a model. Previous successful size was gemma-3-12b:

```bash
docker exec -it ollama-rocm ollama pull gemma3:12b
# or smaller if VRAM is tight:
# docker exec -it ollama-rocm ollama pull gemma3:4b
```

Test the OpenAI compatible endpoint:

```bash
curl http://localhost:11435/v1/models
```

## 3. Wire it into the Drupal instance (on the same minisforum)

Set the env var that the provisioning already understands:

```bash
export AMD_VLLM_URL=http://localhost:11435/v1
```

If your Drupal is running in Docker Compose on the same host:

- From inside the `web` container you may need `host.docker.internal` or the host IP.
- Quick test from inside the web container:
  ```bash
  docker compose exec web curl -s http://host.docker.internal:11435/v1/models || echo "try host IP"
  ```

Common working value on many Linux hosts:
`AMD_VLLM_URL=http://172.17.0.1:11435/v1` (docker0 bridge)

Or run the ollama-rocm with `--network host` and use `http://localhost:11435/v1`.

Then re-provision:

```bash
drush scr /path/to/hackathon-scripts/provision-servers.php
drush aip:discover-models amd_vllm
drush scr /path/to/hackathon-scripts/provision-factcheck.php
drush cr
```

Check:
- `/admin/reports/ai-router-decisions` → should show the AMD-labeled server.
- Content scan on the demo essay → should use the ROCm model (cost 0).

## 4. Make it survive reboots

Create a small systemd unit or just use the docker `--restart`.

Example one-liner service (or use docker compose with an override).

## 5. Fireworks as reliable backup

If ROCm is unstable or you run out of time:

```bash
export FIREWORKS_API_KEY=your_key_here
```

The provisioning already creates the `fireworks` server when the key is present. The route will still prefer the cheap local/AMD one.

## 6. In this repo (for future boots)

Update your `.env` on minisforum:

```
AMD_VLLM_URL=http://localhost:11435/v1
# or the value that works from inside your containers
OLLAMA_MODELS=gemma3:4b   # still used for the internal CPU ollama fallback
```

The compose's internal `ollama` service stays as CPU fallback.

## Notes / Gotchas

- Gemma models can be large. 12B usually needs ~24-30GB VRAM in reasonable quant. Start with 4B or 12B and watch `rocm-smi` during load.
- First pull can take time + bandwidth.
- Ollama ROCm image uses the host's ROCm libraries.
- In decisions log you will see the model id coming from the Ollama server (e.g. `amd_vllm__gemma3_12b` or similar) with cost 0. That's the important part for the pitch.

This gives you a persistent AMD GPU story without depending on the notebook quota.
