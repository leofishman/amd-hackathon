# Drupal AI Factchecker — claim-level content verification on AMD

## Submission facts

| Field | Value |
|---|---|
| Hackathon | AMD Developer Hackathon ACT II (lablab.ai) |
| Track | 3 — Unicorn / Open Innovation |
| Team | Drupal AI Router (Leo Fishman) |
| What it is | Content-integrity suite (factcheck + AI-likelihood + plagiarism) native to Drupal CMS |
| AMD compute usage | All LLM inference (claim extraction + verdicts) served by vLLM 0.16 on ROCm 7.2 on an AMD Instinct GPU; wired into Drupal as the `amd_vllm` server. Evidence: [`evidence/`](evidence/) |
| Core code | [ai_provider_universal](https://www.drupal.org/project/ai_provider_universal) — our GPL-2.0+ module on drupal.org (pre-existing work; the hackathon adds the AMD serving layer + university use case) |
| Run the demo | `cp .env.example .env && docker compose up -d`, then http://localhost:8080 (admin/admin) |
| Verify the claim yourself | Open node 5 → *Content scan* tab: 3 fabricated claims come back CONTRADICTED against the site's own indexed corpus |

Universities pay heavily for plagiarism detection, but plagiarism tools only
answer *"is it copied?"*. An AI-written essay is original text full of
fabricated claims — invisible to every plagiarism scanner. This project
answers the questions those tools can't: **is it true, and who wrote it?** —
natively inside Drupal, the CMS those institutions already run, with all
inference served by an **AMD Instinct GPU (ROCm 7.2 + vLLM)**.

Built on [ai_provider_universal](https://www.drupal.org/project/ai_provider_universal),
our GPL-2.0+ module published on drupal.org (months of prior work; this
hackathon adds the AMD serving layer and the university use case).

## What it does

One click on any content node (the **Content scan** tab) runs three checks:

1. **Factcheck** — atomic claim extraction → evidence retrieval → per-claim
   verdict (`SUPPORTED` / `UNSUPPORTED` / `CONTRADICTED`) with an auditable
   score. Evidence comes from the institution's **own indexed corpus first**
   (Search API), the web second (Tavily, optional). Claims echoed only by
   known-misinformation domains are marked *tainted* — counting against them.
2. **AI-writing likelihood** — a 0–100 score with rationale (honestly
   framed: a hint, not proof).
3. **Verbatim plagiarism** — the text's most distinctive sentences searched
   as exact phrases on the web (Serper, optional).

Every model call and routing decision is logged and auditable at
`/admin/reports/ai-router-decisions`. Everything — models, backends,
evidence sources, trust lists — is Drupal configuration, not code.

## Quick start

```bash
cp .env.example .env    # optionally set AMD_VLLM_URL / TAVILY_API_KEY / SERPER_API_KEY
docker compose up -d    # first boot installs + provisions everything (~3-4 min)
```

Then open http://localhost:8080 (admin / admin). The front page explains the
demo: a fictional university corpus (4 indexed articles) and a **student
essay containing three fabricated claims**. Open the essay's *Content scan*
tab and run the scan — the pipeline finds all three:

| Claim in the essay | Corpus says | Verdict |
|---|---|---|
| "founded in 1875" | founded in 1892 | CONTRADICTED |
| "rare manuscripts tragically lost in the 1953 fire" | they survived | CONTRADICTED |
| "James Holloway won the 1989 Nobel Prize" | no alumnus ever has | CONTRADICTED |

True claims (three campuses, Elena Vasquez and the bridge) come back
SUPPORTED — grounded in the university's own content, not model memory.

## AMD compute

Set `AMD_VLLM_URL` in `.env` to an OpenAI-compatible endpoint served by
vLLM on an AMD GPU (ROCm) and re-run `docker compose up -d`: the
provisioning registers it as the **"AMD Instinct GPU (ROCm + vLLM)"**
server, discovers its models, and points claim extraction and verdict
checking at it. The decisions log then shows every inference call routed to
AMD hardware. Hardware evidence (rocm-smi, vLLM startup log, decision log
screenshots) lives in [`evidence/`](evidence/).

Privacy angle: student work never has to leave institution-controlled AMD
hardware — no SaaS, no third-party data processing agreement.

Without `AMD_VLLM_URL` the stack falls back to a bundled Ollama container
(gemma3:4b, CPU) so the demo runs anywhere.

## Why this beats the closed incumbents

- **Closed tools can't see your corpus.** Verdicts here are grounded in the
  institution's own indexed knowledge, with the evidence shown per claim.
- **Closed tools are black boxes.** Here every decision — model, route,
  cost, verdict — is logged, and the whole pipeline is GPL code you can read.
- **Closed tools are another platform.** This is a module install on the
  CMS already powering a large share of .edu and .gov sites.

## Layout

```
docker-compose.yml         web (Drupal 11) + db (MariaDB) + ollama (fallback)
Dockerfile                 all PHP deps baked at build; module from drupal.org
docker-entrypoint.sh       idempotent install + provisioning on first boot
scripts/                   servers, factcheck, evidence index, demo content
track3/                    submission assets: runbook, video script, deck prompt
evidence/                  AMD hardware evidence (rocm-smi, vLLM logs, screenshots)
```

Roadmap: this provisioning becomes a Drupal **recipe**, making the
factchecker a one-command install on any existing Drupal site.

## Built on

[Drupal 11](https://www.drupal.org) · [Drupal AI](https://www.drupal.org/project/ai)
· [ai_provider_universal](https://www.drupal.org/project/ai_provider_universal)
(ours, GPL-2.0+) · [vLLM](https://github.com/vllm-project/vllm) on
[ROCm](https://www.amd.com/en/products/software/rocm.html) ·
[Ollama](https://ollama.com) (CPU fallback) · Search API
