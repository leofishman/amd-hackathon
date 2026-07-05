# Hybrid Token-Efficient Routing Agent — Drupal AI Router

**AMD Developer Hackathon ACT II · Track 1 · Team "Drupal AI Router"**

A hybrid routing agent that decides, per task and in real time, between
**local models (zero remote tokens)** and **remote models (Fireworks)** —
built not as a standalone script but as configuration on top of
[ai_provider_universal](https://www.drupal.org/project/ai_provider_universal),
an open-source Drupal module with a cost-aware smart router, fact-checking
and usage limits. The agent runs inside a real CMS used by governments and
universities: every decision is configurable through a UI and logged for
audit.

## Quick start

```bash
cp .env.example .env      # add FIREWORKS_API_KEY for remote escalation (optional)
docker compose up -d      # first boot: installs Drupal, pulls the local model,
                          # provisions servers + the smart route. ~2-3 min.
```

Then send a task:

```bash
curl -s -X POST http://localhost:8080/agent/task \
  -H 'Content-Type: application/json' \
  -d '{"task": "What is the chemical symbol for gold?"}'
```

```json
{
  "answer": "Au",
  "routing": {
    "route_id": "hybrid_chat",
    "complexity": "simple",
    "chosen_model": "local_ollama__gemma3_1b",
    "candidates": "1",
    "est_tokens": "12",
    "est_cost": "0",
    "est_cost_worst": "0"
  },
  "elapsed_ms": 3326
}
```

Every response carries its own routing decision: which model was chosen,
why (complexity tier), what it cost and what the worst eligible candidate
would have cost. `est_cost: 0` means the task never left the box.

## Entry points

| Door | Use | How |
|------|-----|-----|
| HTTP | scoring harness / automation | `POST /agent/task` with `{"task": "..."}` (or a raw text body) |
| CLI | scoring harness / debugging | `docker compose exec web drush amd:task "..." --json` |
| Web UI | humans | `http://localhost:8080` (admin / admin) |

All doors share one service (`TaskRunner`): a single point of logic to
re-map to whatever interface the evaluation requires.

## How routing works

1. Each model (local or remote) is a config entity with **cost per token,
   quality tier (1–5) and context length** — prefilled at discovery, all
   editable in the UI.
2. A **smart route** lists candidate models and the minimum tier for
   simple vs complex prompts. Local models cost 0.
3. Per request, the router classifies complexity, filters candidates that
   are capable enough, and picks the **cheapest capable** one. Simple task
   → tiny local model, zero remote tokens. Complex task → escalates only
   as far as needed.
4. Optionally the route **fact-checks** the answer (claim extraction →
   evidence → verdicts) and escalates to a better model when the support
   score is too low.
5. Every decision lands in a log table exposed as a Views page:
   `/admin/reports/ai-router-decisions`.

## Transparency for evaluation

- Machine-readable: every `/agent/task` response embeds the routing row.
- Human-readable: the decisions report lists timestamp, route, complexity,
  chosen model, tokens, chosen vs worst-case cost.
- Nothing is hardcoded: servers, models, costs, tiers, routes and limits
  are Drupal config entities — judges can change any of them in the UI and
  watch decisions change.

## Security stance

The task text is untrusted input:

- It always travels as the `user` message; the system prompt pins the role
  and instructs the model to ignore embedded instructions.
- The runner **never executes** anything from model output — it returns
  text plus metadata, full stop. The anonymous endpoint cannot touch site
  state.
- API keys live only in environment variables (Key module `env` provider);
  they are never written to config or the database.
- Optional fact-checking verifies claims against curated evidence — a
  defense against manipulated answers.

## Layout

```
docker-compose.yml        web (Drupal 11 + drush baked in) + db (MariaDB) + ollama
Dockerfile                all composer deps installed at build time
docker-entrypoint.sh      installs + provisions on first boot, idempotent
scripts/
  setup-hackathon.sh      site install, model pull, provisioning, perf tuning
  provision-servers.php   Ollama (zero cost) + Fireworks (env key) servers
  provision-route.php     builds the hybrid_chat route from discovered models
modules/amd_hackathon/    TaskRunner service + HTTP controller + drush command
STRATEGY.md               decisions and kickoff checklist
```

## Tuning at the kickoff

- `OLLAMA_MODELS` in `.env`: comma-separated models to pull at first boot.
- Route tiers / candidates / fact-checking: `/admin/config/ai/universal/routes`
  or edit `scripts/provision-route.php` and rebuild.
- Harness in/out format: `modules/amd_hackathon/src/Controller/TaskController.php`.

## Built on

- [Drupal 11](https://www.drupal.org) + [AI module](https://www.drupal.org/project/ai)
- [ai_provider_universal](https://www.drupal.org/project/ai_provider_universal)
  (our module: universal provider, smart router, factcheck — GPL-2.0+)
- [Ollama](https://ollama.com) for local inference,
  [Fireworks](https://fireworks.ai) for remote escalation
