#!/bin/bash
set -e
cd /opt/drupal

OLLAMA_URL="${OLLAMA_URL:-http://ollama:11434}"
OLLAMA_MODELS="${OLLAMA_MODELS:-gemma3:1b}"

echo "=== AMD Hackathon setup ==="

echo "--- Installing Drupal..."
drush site-install standard \
  --db-url="mysql://$DB_USER:$DB_PASSWORD@$DB_HOST/$DB_NAME" \
  --site-name="Drupal AI Factchecker on AMD" \
  --account-name=admin --account-pass="${ADMIN_PASS:-admin}" --account-mail=admin@example.com \
  --yes

echo "--- Enabling modules..."
# rest+serialization first: the router log view ships a rest_export display
# and older module releases don't declare the dependency (crash on install).
drush pm:enable rest serialization --yes
drush pm:enable key ai eca views field text search_api search_api_db ai_provider_universal \
  ai_provider_universal_router ai_provider_universal_factcheck amd_hackathon --yes

echo "--- Pulling local models: $OLLAMA_MODELS"
for model in ${OLLAMA_MODELS//,/ }; do
  echo "    pulling $model ..."
  curl -sf "$OLLAMA_URL/api/pull" -d "{\"model\": \"$model\"}" > /dev/null \
    || echo "    WARNING: pull of $model failed (ollama unreachable?)"
done

echo "--- Provisioning AI servers (local Ollama + AMD GPU + Fireworks)..."
drush scr /hackathon-scripts/provision-servers.php

echo "--- Discovering models..."
drush aip:discover-models local_ollama || echo "WARNING: ollama discovery failed"
if [ -n "$AMD_VLLM_URL" ]; then
  drush aip:discover-models amd_vllm || echo "WARNING: AMD server discovery failed"
fi
if [ -n "$FIREWORKS_API_KEY" ]; then
  drush aip:discover-models fireworks || echo "WARNING: fireworks discovery failed"
fi

echo "--- Creating the smart route..."
drush scr /hackathon-scripts/provision-route.php

echo "--- Performance tuning..."
drush config:set system.performance cache.page.max_age 3600 --yes
drush config:set system.performance css.preprocess 1 --yes
drush config:set system.performance js.preprocess 1 --yes
drush pm:enable big_pipe --yes

echo "--- Demo content..."
drush scr /hackathon-scripts/create-demo.php || true

echo "--- Factcheck (evidence index + models)..."
drush scr /hackathon-scripts/provision-factcheck.php

echo "--- Trusted sites (recipes + seeds)..."
drush scr /hackathon-scripts/provision-trusted-sites.php || true

echo "--- Full demo content (varied examples)..."
drush scr /hackathon-scripts/create-full-demo-content.php || true

echo "--- Indexing evidence corpus..."
drush search-api:index || echo "WARNING: indexing failed"

drush cache:rebuild
echo "=== Setup complete ==="
echo "Routing decision log: /admin/reports/ai-router-decisions"
