#!/usr/bin/env python3
"""LoRA fine-tune of a tiny Gemma as router classifier / answer verifier.

Device-agnostic: uses ROCm if torch sees the GPU (HSA_OVERRIDE_GFX_VERSION
may be needed on APUs), otherwise CPU — a 270M model with LoRA rank 8 and a
few thousand examples trains in minutes-to-hours either way.
Note: Vulkan is inference-only (llama.cpp); training is ROCm or CPU.

Dataset: JSONL with {"prompt": "...", "label": "..."} per line (see
DATASET_PROMPT.md). Labels are free-form single words; the same script
trains the complexity classifier (simple/complex) or the verifier (yes/no).

Usage:
  pip install torch transformers peft datasets
  python3 train_classifier.py dataset.jsonl --model google/gemma-3-270m-it

Afterwards, serve with Ollama:
  python3 llama.cpp/convert_hf_to_gguf.py out/merged --outfile classifier.gguf
  ollama create task-classifier -f Modelfile   # FROM ./classifier.gguf
Then point classifier_model / verifier_model (ai_provider_universal_router)
at the discovered model. No code changes.
"""

import argparse
import json

import torch
from datasets import Dataset
from peft import LoraConfig, get_peft_model
from transformers import (
    AutoModelForCausalLM,
    AutoTokenizer,
    Trainer,
    TrainingArguments,
)

SYSTEM = "Classify the task. Reply with exactly one word."


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("dataset", help="JSONL file with prompt/label pairs")
    ap.add_argument("--model", default="google/gemma-3-270m-it")
    ap.add_argument("--out", default="out")
    ap.add_argument("--epochs", type=int, default=3)
    ap.add_argument("--lr", type=float, default=2e-4)
    args = ap.parse_args()

    device = "cuda" if torch.cuda.is_available() else "cpu"  # cuda == ROCm too
    print(f"device: {device}")

    tok = AutoTokenizer.from_pretrained(args.model)
    model = AutoModelForCausalLM.from_pretrained(
        args.model,
        torch_dtype=torch.bfloat16 if device == "cuda" else torch.float32,
    ).to(device)

    lora = LoraConfig(
        r=8, lora_alpha=16, lora_dropout=0.05,
        target_modules=["q_proj", "k_proj", "v_proj", "o_proj"],
        task_type="CAUSAL_LM",
    )
    model = get_peft_model(model, lora)
    model.print_trainable_parameters()

    rows = [json.loads(line) for line in open(args.dataset) if line.strip()]

    def to_text(row):
        msgs = [
            {"role": "user", "content": f"{SYSTEM}\n\n{row['prompt']}"},
            {"role": "assistant", "content": row["label"]},
        ]
        return {"text": tok.apply_chat_template(msgs, tokenize=False)}

    ds = Dataset.from_list([to_text(r) for r in rows])
    ds = ds.map(
        lambda b: tok(b["text"], truncation=True, max_length=512),
        batched=True, remove_columns=["text"],
    )

    def collate(features):
        batch = tok.pad(features, return_tensors="pt")
        batch["labels"] = batch["input_ids"].clone()
        return batch

    Trainer(
        model=model,
        args=TrainingArguments(
            output_dir=args.out,
            num_train_epochs=args.epochs,
            learning_rate=args.lr,
            per_device_train_batch_size=4,
            gradient_accumulation_steps=4,
            logging_steps=20,
            save_strategy="epoch",
            bf16=device == "cuda",
            report_to=[],
        ),
        train_dataset=ds,
        data_collator=collate,
    ).train()

    merged = model.merge_and_unload()
    merged.save_pretrained(f"{args.out}/merged")
    tok.save_pretrained(f"{args.out}/merged")
    print(f"merged model at {args.out}/merged — convert to GGUF and `ollama create`")


if __name__ == "__main__":
    main()
