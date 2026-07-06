#!/usr/bin/env python3
"""
Fine-tuning script for Gemma 4 E4B (or similar 2B-9B models) as:
- Complexity classifier (simple / complex)
- Answer verifier (yes / no)

Optimized for AMD GPUs (ROCm) on MiniSforum.

Recommended:
- Use --qlora for 4B+ models (4-bit + LoRA)
- Good for the hackathon routing agent (local-first with verification)

Dataset format (one JSON per line):
{"prompt": "the input task or task+answer", "label": "simple"}   # or "complex", "yes", "no"

Usage examples:
  # QLoRA on Gemma 4 E4B (recommended)
  python train_gemma_e4b.py dataset_classifier.jsonl --model <gemma-4-e4b-or-e2b-hf-id> --qlora

  # Or for verifier
  python train_gemma_e4b.py dataset_verifier.jsonl --model <gemma-4-e4b-or-e2b-hf-id> --qlora --epochs 4

# Note: Do not run training until after kickoff when real tasks are known.
# Use the provided synthetic data + add real examples from revealed tasks.
# This avoids overfitting to synthetic data.

After training:
  1. Merge LoRA
  2. Convert to GGUF
  3. Load into Ollama
  4. Register in ai_provider_universal as local model
"""

import argparse
import json
import os

import torch
from datasets import Dataset
from peft import LoraConfig, get_peft_model, PeftModel
from transformers import (
    AutoModelForCausalLM,
    AutoTokenizer,
    Trainer,
    TrainingArguments,
    BitsAndBytesConfig,
)

SYSTEM_CLASSIFIER = "Classify the task. Reply with exactly one word: simple or complex."
SYSTEM_VERIFIER = "Does the answer correctly and completely solve the task? Reply with exactly one word: yes or no."


def load_dataset(path: str):
    rows = []
    with open(path) as f:
        for line in f:
            if line.strip():
                rows.append(json.loads(line))
    return rows


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("dataset", help="Path to dataset.jsonl")
    parser.add_argument("--model", required=True, help="HF model id for Gemma 4 E4B or E2B (e.g. the actual ID for gemma-4-e4b-it or equivalent available for training)")
    parser.add_argument("--out", default="out_e4b", help="Output directory")
    parser.add_argument("--epochs", type=int, default=3)
    parser.add_argument("--lr", type=float, default=2e-4)
    parser.add_argument("--qlora", action="store_true", help="Use 4-bit QLoRA (recommended for 4B+)")
    parser.add_argument("--lora_r", type=int, default=16)
    parser.add_argument("--batch_size", type=int, default=2)
    parser.add_argument("--grad_accum", type=int, default=8)
    args = parser.parse_args()

    device = "cuda" if torch.cuda.is_available() else "cpu"
    print(f"Device: {device}")

    # Tokenizer
    tok = AutoTokenizer.from_pretrained(args.model, use_fast=True)
    if tok.pad_token is None:
        tok.pad_token = tok.eos_token

    # Model loading
    if args.qlora:
        bnb_config = BitsAndBytesConfig(
            load_in_4bit=True,
            bnb_4bit_quant_type="nf4",
            bnb_4bit_compute_dtype=torch.bfloat16 if device == "cuda" else torch.float32,
            bnb_4bit_use_double_quant=True,
        )
        model = AutoModelForCausalLM.from_pretrained(
            args.model,
            quantization_config=bnb_config,
            device_map="auto",
            torch_dtype=torch.bfloat16 if device == "cuda" else torch.float32,
        )
    else:
        model = AutoModelForCausalLM.from_pretrained(
            args.model,
            torch_dtype=torch.bfloat16 if device == "cuda" else torch.float32,
            device_map="auto",
        )

    # LoRA config (good defaults for Gemma)
    lora_config = LoraConfig(
        r=args.lora_r,
        lora_alpha=32,
        lora_dropout=0.05,
        bias="none",
        target_modules=["q_proj", "k_proj", "v_proj", "o_proj", "gate_proj", "up_proj", "down_proj"],
        task_type="CAUSAL_LM",
    )
    model = get_peft_model(model, lora_config)
    model.print_trainable_parameters()

    # Load data
    rows = load_dataset(args.dataset)

    def format_example(row):
        # Heuristic: if prompt contains "Answer:" or "Task:" → verifier style
        if "Answer:" in row["prompt"] or "Does the answer" in row["prompt"]:
            system = SYSTEM_VERIFIER
        else:
            system = SYSTEM_CLASSIFIER

        messages = [
            {"role": "system", "content": system},
            {"role": "user", "content": row["prompt"]},
            {"role": "assistant", "content": row["label"]},
        ]
        text = tok.apply_chat_template(messages, tokenize=False, add_generation_prompt=False)
        return {"text": text}

    processed = [format_example(r) for r in rows]
    ds = Dataset.from_list(processed)

    def tokenize(batch):
        return tok(
            batch["text"],
            truncation=True,
            max_length=1024,
            padding=False,
        )

    ds = ds.map(tokenize, batched=True, remove_columns=["text"])

    def data_collator(features):
        batch = tok.pad(features, return_tensors="pt", padding=True)
        batch["labels"] = batch["input_ids"].clone()
        return batch

    training_args = TrainingArguments(
        output_dir=args.out,
        num_train_epochs=args.epochs,
        learning_rate=args.lr,
        per_device_train_batch_size=args.batch_size,
        gradient_accumulation_steps=args.grad_accum,
        warmup_steps=20,
        logging_steps=10,
        save_strategy="epoch",
        save_total_limit=2,
        bf16=(device == "cuda"),
        optim="adamw_torch" if not args.qlora else "paged_adamw_8bit",
        report_to=[],
        remove_unused_columns=False,
    )

    trainer = Trainer(
        model=model,
        args=training_args,
        train_dataset=ds,
        data_collator=data_collator,
    )

    print("Starting training...")
    trainer.train()

    print("Merging LoRA weights...")
    merged = model.merge_and_unload()
    merged.save_pretrained(f"{args.out}/merged")
    tok.save_pretrained(f"{args.out}/merged")

    print(f"\n✅ Done! Merged model at: {args.out}/merged")
    print("Next steps:")
    print("  1. Convert to GGUF: python llama.cpp/convert_hf_to_gguf.py out/merged --outfile gemma4-e4b-finetuned.gguf")
    print("  2. ollama create gemma4-e4b-finetuned -f Modelfile")
    print("  3. Use in ai_provider_universal as local model + set as classifier_model or verifier_model")


if __name__ == "__main__":
    main()
