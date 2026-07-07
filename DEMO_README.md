# AMD Hackathon Demo Site - Full Feature Walkthrough

This Drupal site demonstrates the `ai_provider_universal_factcheck` module with AMD GPU inference.

## Quick Start

1. `docker compose up -d`
2. Login as admin/admin (or judge user)
3. Go to a node with "Content scan" tab and run the scan.

## Demo Content (run to populate)

```bash
drush scr /hackathon-scripts/create-demo.php
drush scr /hackathon-scripts/create-enhanced-demo.php
drush scr /hackathon-scripts/create-media-bias-demo.php
drush scr /hackathon-scripts/create-full-demo-content.php
drush search-api:index
```

## Key Features Demonstrated

### 1. Local Corpus Factchecking
- Evidence comes first from the site's own indexed articles.
- Example: "Aldford University has three campuses" → SUPPORTED from corpus.

### 2. Trusted Sites & Reputation
- Sites have reputation scores (-10 to +10).
- Positive reputation sources are preferred for evidence.
- Negative reputation sources can mark a claim as "tainted".

### 3. Ideological / Media Bias
- `field_bias` stores Left / Center / Right.
- Run the Venezuela earthquake claim.
- See how Fox News style (right) vs RT style (left) sources are weighted differently.
- Discrepancy analysis shows bias spread.

### 4. Importing External Bias Ratings
```bash
drush factcheck:sync-bias-ratings
```
Uses data from MediaBiasFactCheck-style sources (extendable JSON or API).

### 5. Hybrid Routing + AMD
- Check `/admin/reports/ai-router-decisions`
- Simple claims often use local AMD model (cost 0).
- Complex claims may escalate.

### 6. Full Pipeline
- Claim extraction
- Evidence retrieval (local + web with trusted filter)
- Verdict per claim
- AI likelihood + Plagiarism (if keys set)
- Overall score

## Nodes to Test

- Student essay (main fabricated claims)
- Venezuela earthquake analysis (media bias demo)
- Aldford simple claims
- Complex student essay

## Trusted Sites Management

Admin can curate at `/admin/content?type=trusted_site`

Reputation and bias directly influence factcheck behavior.

## Notes for Judges

- Everything is configurable in Drupal (no code changes needed for new sources/models).
- Local AMD inference via ROCm for most work.
- Full audit trail.
- GPL open source.

See the main module docs for technical details.
