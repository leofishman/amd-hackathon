# Fine-tuning pipeline: tiny Gemma as router classifier / verifier

End-to-end: dataset → LoRA training on the MiniSforum → GGUF → published
on Hugging Face → pulled automatically by the Docker deliverable.

## 0. Which Gemma

Train the **smallest Gemma available** — the output is one word, size buys
nothing. If the kickoff reveals a small Gemma 4 variant, use it; otherwise
`google/gemma-3-270m-it` (the script default). The big Gemma answers
tasks; the tiny one routes/verifies. Nothing is hardcoded: `--model` takes
any HF id.

## 1. Dataset

Prompts for Grok + validation checklist: [DATASET_PROMPT.md](DATASET_PROMPT.md).
Variant A trains the complexity classifier (labels `simple`/`complex`),
variant B the answer verifier (labels `yes`/`no`). Always run the
validation checklist on Grok's output before training. On kickoff day, add
20-50 examples built from the real revealed tasks — they outweigh
thousands of synthetic ones.

## 2. Train (MiniSforum)

```bash
pip install torch transformers peft datasets
# ROCm box: install the rocm torch wheel instead and, on APUs, export
# HSA_OVERRIDE_GFX_VERSION as usual. CPU works too at this model size.
python3 train_classifier.py dataset.jsonl --model google/gemma-3-270m-it
```

Merged model lands in `out/merged`. Quick sanity check before converting:

```bash
python3 - <<'EOF'
from transformers import pipeline
p = pipeline("text-generation", model="out/merged")
print(p("Classify the task. Reply with exactly one word.\n\nProve Fermat's little theorem.", max_new_tokens=3))
EOF
```

## 3. Convert to GGUF

```bash
git clone --depth 1 https://github.com/ggml-org/llama.cpp
pip install -r llama.cpp/requirements.txt
python3 llama.cpp/convert_hf_to_gguf.py out/merged --outfile task-classifier.gguf
# Optional: quantize (llama.cpp build needed) — Q8_0 is plenty at 270M:
# llama.cpp/build/bin/llama-quantize task-classifier.gguf task-classifier-q8.gguf Q8_0
```

## 4. Publish on Hugging Face

Create a public model repo (e.g. `leofishman/task-classifier-gguf`) and
upload the `.gguf` (web UI, or `hf upload leofishman/task-classifier-gguf
task-classifier.gguf`). Ollama pulls GGUFs straight from HF — no Ollama
registry account needed.

## 5. Wire into the deliverable (zero code)

In `.env`:

```bash
# The container pulls every model listed here at first boot:
OLLAMA_MODELS=gemma3:1b,hf.co/leofishman/task-classifier-gguf
```

After boot, discovery picks it up as an `ai_universal_model` entity. Point
the router at it:

```bash
# As complexity classifier:
docker compose exec web drush config:set \
  ai_provider_universal_router.settings classifier_model <entity_id> -y
# And/or as answer verifier: set AGENT_VERIFIER_MODEL in .env before first
# boot, or edit the route at /admin/config/ai/providers/universal/routes.
```

For local testing outside Docker: `ollama pull hf.co/leofishman/task-classifier-gguf`
or `ollama create task-classifier -f Modelfile` with `FROM ./task-classifier.gguf`.

## Fallback ladder (if training misbehaves on Sunday night)

1. ROCm fails → CPU (fine at 270M).
2. Fine-tune fails → few-shot: put labeled examples in the classifier
   prompt of a stock tiny Gemma. The rules score prompt-based and
   fine-tuned approaches identically.
3. Classifier is unreliable → heuristics only (`classifier_model` empty);
   the verifier route still catches bad local answers.
