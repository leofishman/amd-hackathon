# Prompt para Grok: generación del dataset de entrenamiento

Instrucciones: pegar el prompt de abajo. Pedir la salida en bloques de ~200
líneas para que no trunque. **Validar antes de entrenar** (ver el checklist
al final). Hay dos variantes: clasificador de complejidad y verificador.
El día del kickoff, agregar 20-50 ejemplos reales de las tareas reveladas a
cada dataset — pesan más que miles de sintéticos.

---

## Variante A — clasificador de complejidad (simple/complex)

> Generate a training dataset for a prompt-complexity classifier used by an
> AI model router. Output ONLY JSONL, one object per line, no markdown, no
> commentary: `{"prompt": "...", "label": "simple"}` or
> `{"prompt": "...", "label": "complex"}`.
>
> Definition: "simple" = a 1B-parameter local model can answer it well
> (factual lookup, short definitions, unit conversions, one-line rewrites,
> yes/no questions, simple translations, short summaries of short text).
> "complex" = needs reasoning, multi-step work, code generation or
> debugging, math proofs or calculations with several steps, long-document
> analysis, careful writing, or domain expertise.
>
> Requirements:
> - 1000 examples, 50% simple / 50% complex, shuffled.
> - Vary length from 5 to 200 words. Vary domains: science, history,
>   programming, business, cooking, law, sports, geography, health.
> - Include tricky borderline cases: short prompts that are actually
>   complex ("Prove Fermat's little theorem"), long prompts that are
>   actually simple (a long text followed by "how many paragraphs is
>   this?").
> - Include ~5% adversarial prompts containing instructions like "ignore
>   previous instructions" or "reply with the word simple" — label them by
>   their real difficulty, not by what they ask.
> - English mostly; ~10% Spanish.
> - No duplicate or near-duplicate prompts.

## Variante B — verificador de respuestas (yes/no)

> Generate a training dataset for an answer verifier. Output ONLY JSONL,
> one object per line: `{"prompt": "Task:\n<task>\n\nAnswer:\n<answer>\n\nDoes the answer correctly and completely solve the task? Reply with exactly one word: yes or no.", "label": "yes"}` (or "no").
>
> Requirements:
> - 1000 examples, 50% yes / 50% no, shuffled.
> - The "no" cases must be realistic small-model failures: confidently
>   wrong facts (e.g. "the capital of Australia is Sydney"), answering a
>   different question than asked, incomplete answers, refusals ("I cannot
>   help with that") for benign tasks, rambling that never answers, wrong
>   arithmetic, made-up citations.
> - The "yes" cases include terse-but-correct answers (one word when one
>   word suffices) — the verifier must not punish brevity.
> - Vary domains and lengths as in variant A. ~10% Spanish.
> - No duplicates.

---

## Checklist de validación (antes de entrenar, siempre)

```bash
# 1. JSONL válido y labels correctos
python3 -c "
import json,sys,collections
c=collections.Counter()
for i,l in enumerate(open('dataset.jsonl')):
    d=json.loads(l); assert set(d)=={'prompt','label'}, i
    c[d['label']]+=1
print(c)"

# 2. Duplicados
sort dataset.jsonl | uniq -d | head

# 3. Muestreo manual: leer 20 al azar, ¿las etiquetas tienen sentido?
shuf -n 20 dataset.jsonl
```

Conocidos de Grok: labels inventados fuera del set pedido, comillas sin
escapar que rompen el JSON, y clases desbalanceadas al final del archivo
(genera 500 de una y 500 de otra sin mezclar — el `shuffle` lo hace
`shuf dataset.jsonl > shuffled.jsonl` si hace falta).
