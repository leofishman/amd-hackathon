# Post-Kickoff Fine-tuning Checklist (Gemma 4 E4B)

Run this sequence once the hackathon reveals the exact tasks and model names (expected in ~15 hours).

## 1. Update Datasets (most important step)

- Take the revealed tasks.
- Create 30-80 high-quality examples following the format in `DATASET_PROMPT.md`.
- Add them to both:
  - `dataset_classifier.jsonl` (simple / complex)
  - `dataset_verifier.jsonl` (yes / no)
- Mix with the existing synthetic data.
- Shuffle and validate:

```bash
shuf dataset_classifier.jsonl > dataset_classifier_final.jsonl
python3 -c "
import json, collections
c = collections.Counter()
for line in open('dataset_classifier_final.jsonl'):
    d = json.loads(line)
    c[d['label']] += 1
print(c)
"
```

## 2. Fine-tune on MiniSforum

```bash
# Classifier
python3 train_gemma_e4b.py dataset_classifier_final.jsonl \
  --model google/gemma-2-2b-it \
  --qlora \
  --epochs 3 \
  --out out_gemma4_e4b_classifier

# Verifier (recommended)
python3 train_gemma_e4b.py dataset_verifier_final.jsonl \
  --model google/gemma-2-2b-it \
  --qlora \
  --epochs 4 \
  --out out_gemma4_e4b_verifier
```

## 3. Convert to GGUF + Ollama

```bash
# Clone llama.cpp if you don't have it
git clone https://github.com/ggerganov/llama.cpp --depth 1
cd llama.cpp && make -j

# Convert
python3 convert_hf_to_gguf.py ../out_gemma4_e4b_verifier/merged \
  --outfile gemma4-e4b-verifier.gguf

# Create Ollama model
ollama create gemma4-e4b-verifier -f - <<EOF
FROM ./gemma4-e4b-verifier.gguf
TEMPLATE """{{ if .System }}<|im_start|>system
{{ .System }}<|im_end|>
{{ end }}{{ if .Prompt }}<|im_start|>user
{{ .Prompt }}<|im_end|>
<|im_start|>assistant
{{ end }}{{ .Response }}<|im_end|>"""
PARAMETER stop "<|im_end|>"
EOF
```

## 4. Test Locally

```bash
ollama run gemma4-e4b-verifier "Task: What is 2+2?\n\nAnswer: 4\n\nDoes the answer solve the task? Reply yes or no."
```

Should reply cleanly with "yes" or "no".

## 5. Integrate into the Hackathon Deliverable

- Put the GGUF in a place the container can pull, or use `ollama create` in the setup script.
- Update `.env`:
  ```
  OLLAMA_MODELS=gemma-2-2b-it,gemma4-e4b-verifier
  ```
- Point the route:
  ```bash
  drush config:set ai_provider_universal_router.settings verifier_model "local_ollama:gemma4-e4b-verifier" -y
  ```

## 6. Quick Validation Before Submission

Run 20-30 sample tasks (from the real set if possible) and check:

```bash
docker compose exec web drush amd:task "your real task here"
```

Then inspect:
```bash
curl http://localhost/agent-decisions.json | jq '.[0:5]'
```

Goal: as many tasks as possible solved with **local model only** (0 remote tokens) while passing factcheck.

Good luck!
