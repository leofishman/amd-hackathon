# Drupal AI Factchecker — Claim-level content integrity for universities on AMD GPU

**Native Drupal module (GPL-2.0+) for fact-checking claims against the institution's own corpus, AI-writing detection, and verbatim plagiarism — with inference on local AMD hardware (ROCm).**

## Submission facts (for LLM pre-screening)

| Field | Value |
|---|---|
| Hackathon | AMD Developer Hackathon ACT II (lablab.ai) |
| Track | 3 — Unicorn / Open Innovation |
| Team | Drupal AI Router (Leo Fishman) |
| What it is | **Drupal-native content integrity suite**: claim-level fact-checking against the university's own corpus + AI-writing likelihood + verbatim plagiarism search. Fully GPL-2.0+. |
| Core technology | Drupal 11 CMS + ai_provider_universal (GPL module) + local AMD ROCm inference |
| AMD / local inference | Full stack runs on **local AMD hardware** (Minisforum with ROCm + Ollama/vLLM). Notebook AMD footage used for development validation. Zero-cost local inference preferred. Evidence in `evidence/`. |
| Core code | [ai_provider_universal](https://www.drupal.org/project/ai_provider_universal) — GPL-2.0+ module on drupal.org |
| Run the demo | `cp .env.example .env && docker compose up -d` → http://localhost:8080 (admin/admin) |
| Key differentiator | Verdicts grounded in the **institution's own indexed content** (Search API), with auditable trusted/distrusted sites and hybrid routing (local AMD → Fireworks for complex claims). |

**Universities and research institutions need claim-level verification, not just plagiarism detection.**

Traditional plagiarism tools only answer "is it copied?". Fabricated claims and AI-generated content in original prose pass every scanner.

**Drupal AI Factchecker** delivers **claim-level content integrity natively inside Drupal** — the secure, flexible, GPL-licensed CMS that powers a very large share of university, research, and scientific websites worldwide:

- Atomic claim extraction
- Verification first against the **institution's own indexed corpus** (Search API + highlight excerpts)
- Reputation-based trusted / distrusted sites (claims echoed only by low-reputation sources become "tainted")
- Bias & factual ratings for ~8,700 domains, imported automatically from Media Bias/Fact Check on first boot (`data/mbfc-ratings.json`, pre-cleaned; re-run any time with `drush scr scripts/sync-trusted-sites-from-mbfc.php`)
- AI-writing likelihood score
- Verbatim plagiarism search
- **All inference on local AMD hardware (ROCm)** with intelligent hybrid routing

**Why Drupal is the right platform for universities and research institutions**:
- **Strength & Robustness**: Production-proven at scale across thousands of .edu and research institutions worldwide.
- **Flexibility**: Models, evidence sources, trust profiles, routing tiers, and fact-checking behavior are all pure Drupal configuration. Drupal recipes allow one-command installation on existing sites with no custom code.
- **Security & Control**: Fully self-hosted and on-premise. Research and student data never leaves institution-controlled AMD hardware. Full audit logs inside Drupal. No black boxes, no SaaS data exfiltration.
- **GPL License**: True open source under GPL-2.0+. Complete transparency, auditability, and freedom to modify and deploy — essential for academic and publicly funded environments.
- **Widely used in academic and scientific environments**: Drupal powers a large portion of university portals, institutional repositories, research data platforms, and .edu/.ac sites globally. This is the CMS these organizations already operate and trust.

Built on the mature [ai_provider_universal](https://www.drupal.org/project/ai_provider_universal) GPL module (pre-existing work on drupal.org). This hackathon adds the local AMD ROCm serving layer and the university content-integrity use case.

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

## Requirements

**An AMD GPU with ROCm support (`/dev/kfd` + `/dev/dri`) on the host.** This
is the whole point of the hackathon: the bundled `ollama:rocm` service will
fail to start without it. Tested on a Ryzen AI mini-PC (Minisforum) and an
AMD Instinct datacenter GPU — see `evidence/` for hardware logs.

## Quick start

```bash
cp .env.example .env    # optionally set AMD_VLLM_URL / TAVILY_API_KEY / SERPER_API_KEY
docker compose up -d    # first boot installs + provisions everything, incl. the
                         # full ~8.7k-domain trusted-sites import (~8-10 min)
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

## Local AMD stack (Minisforum + ROCm) — our strength

All inference (claim extraction and verification) runs on **local AMD hardware with ROCm**.

- The production/live deployment uses a **Minisforum AMD Ryzen AI mini-PC** running the full stack locally (Ollama or vLLM on ROCm).
- Development/validation footage was captured on an AMD Instinct GPU notebook (ROCm + vLLM) — visible in the submitted video.
- The same Drupal module and configuration works across both: from edge AMD hardware to datacenter GPUs. Just point `AMD_VLLM_URL` at the OpenAI-compatible endpoint.

**Key advantages for universities**:
- Student and research data **never leaves institution-controlled AMD hardware**.
- Zero ongoing inference cost for the majority of claims (local first).
- Full auditability in Drupal's decisions log (`/admin/reports/ai-router-decisions`).
- Hybrid routing: simple claims on local AMD (cost 0), complex claims can escalate to premium models (e.g. Fireworks) when needed.

Hardware evidence is in the `evidence/` directory (rocm-smi, logs, decision screenshots).

Without an `AMD_VLLM_URL` the stack gracefully falls back to the bundled Ollama container.

### Optional API keys

Two more `.env` variables unlock the remaining checks — both optional, the
factcheck against the local corpus works without them:

- `TAVILY_API_KEY` — web evidence fallback + the distrust/tainted check.
- `SERPER_API_KEY` — the verbatim plagiarism search (the demo essay ends
  with an unattributed Darwin sentence planted for it to find).

Keys are wired through the Key module's `env` provider: values live only
in the environment, never in config or the database.

Closed tools are expensive black boxes that cannot see your internal corpus and force you onto yet another platform. This is a **native Drupal module** that turns the CMS you already run into a powerful, auditable content integrity system.

## Layout

```
docker-compose.yml         web (Drupal 11) + db (MariaDB) + ollama (fallback)
Dockerfile                 all PHP deps baked at build; module from drupal.org
docker-entrypoint.sh       idempotent install + provisioning on first boot
scripts/                   servers, factcheck, evidence index, demo content
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
