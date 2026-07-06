# Fine-tuning pipeline: tiny Gemma as router classifier / verifier

End-to-end: dataset → LoRA training on the MiniSforum → GGUF → published
on Hugging Face → pulled automatically by the Docker deliverable.

## 0. Which Gemma

We are fine-tuning **Gemma 4 E4B IT Thinking** (or the E2B variant) on the MiniSforum for stronger local performance in the routing agent.

The goal is a better local model that can handle more tasks locally (0 remote tokens in scoring) as a classifier, verifier, or even responder, while the router decides when to escalate.

The training script supports any HF model via `--model`. Use the actual ID for Gemma 4 E4B/E2B:
- Use QLoRA (4-bit) for efficiency on AMD GPU (MiniSforum).
- Output merged model, convert to GGUF for Ollama.
- Wire as local model in ai_provider_universal for the hybrid routes.

## 1. Dataset

Prompts for generating data: [DATASET_PROMPT.md](DATASET_PROMPT.md).
Variant A: complexity classifier (`simple`/`complex`).
Variant B: answer verifier (`yes`/`no`).

**Important**: Wait for the actual hackathon tasks (revealed at kickoff). Add 30-100 real examples from them to the synthetic data before training. Real task data is far more valuable than synthetic and reduces overfitting risk.

We have starter datasets (classifier ~150+, verifier ~80+). Expand carefully with variety, borderline cases, and adversarial examples, but prioritize quality over quantity. Balance classes. Validate with the checklist in DATASET_PROMPT.md before training.

## 2. Train on MiniSforum (Gemma 4 E4B)

We are fine-tuning **Gemma 4 E4B IT Thinking** (or E2B) .

Recommended approach (QLoRA for efficiency on AMD GPU):

```bash
# Install dependencies (use ROCm wheels for AMD)
pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/rocm6.1
pip install "transformers>=4.41" peft bitsandbytes datasets accelerate

# Train with QLoRA - replace with actual Gemma 4 E4B/E2B HF id
python3 train_gemma_e4b.py dataset_classifier.jsonl --model <gemma-4-e4b-hf-id> --qlora --epochs 3

# Or for the verifier
python3 train_gemma_e4b.py dataset_verifier.jsonl --model <gemma-4-e4b-hf-id> --qlora
```

After training you will have `out_e4b/merged`.

Quick test:
```bash
python3 - <<'EOF'
from transformers import AutoTokenizer, AutoModelForCausalLM
tok = AutoTokenizer.from_pretrained("out_e4b/merged")
model = AutoModelForCausalLM.from_pretrained("out_e4b/merged")
inputs = tok("Classify the task. Reply with exactly one word.\n\nProve that there are infinitely many primes.", return_tensors="pt")
print(tok.decode(model.generate(**inputs, max_new_tokens=5)[0]))
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

After converting to GGUF and creating the Ollama model:

```bash
# In your hackathon .env
OLLAMA_MODELS=gemma-2-2b-it,gemma4-e4b-finetuned
```

Then in the Drupal site (after `docker compose up`):

```bash
# Register the new local model
drush aip:discover-models local_ollama

# Point the smart route to use it as classifier or verifier
drush config:set ai_provider_universal_router.settings classifier_model "local_ollama:gemma4-e4b-finetuned" -y
```

Or set `AGENT_VERIFIER_MODEL` before first boot.

## 6. Post-Kickoff Workflow (run this in ~15 hours)

1. Wait for official task list + exact model names.
2. Add 30-80 real examples from the revealed tasks into both datasets (use the template in DATASET_PROMPT.md).
3. Re-run the training script with the real data mixed in.
4. Re-convert to GGUF and update Ollama.
5. Test locally on MiniSforum with real tasks.
6. Update the Docker deliverable (it will pull the new GGUF via Ollama).

**Rule of thumb**: The more examples that match the actual scoring tasks, the higher the chance the local E4B will handle them without calling Fireworks.

## Fallback ladder (if training misbehaves on Sunday night)

1. ROCm fails → CPU (fine at 270M).
2. Fine-tune fails → few-shot: put labeled examples in the classifier
   prompt of a stock tiny Gemma. The rules score prompt-based and
   fine-tuned approaches identically.
3. Classifier is unreliable → heuristics only (`classifier_model` empty);
   the verifier route still catches bad local answers.
