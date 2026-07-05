#!/bin/bash
set -e
cd /opt/drupal

OLLAMA_URL="${OLLAMA_URL:-http://ollama:11434}"
OLLAMA_MODELS="${OLLAMA_MODELS:-gemma3:1b}"

echo "=== AMD Hackathon setup ==="

echo "--- Installing Drupal..."
drush site-install standard \
  --db-url="mysql://$DB_USER:$DB_PASSWORD@$DB_HOST/$DB_NAME" \
  --site-name="AMD Token-Efficient Agent" \
  --account-name=admin --account-pass=admin --account-mail=admin@example.com \
  --yes

echo "--- Enabling modules..."
drush pm:enable key ai eca views ai_provider_universal \
  ai_provider_universal_router ai_provider_universal_factcheck amd_hackathon --yes

echo "--- Pulling local models: $OLLAMA_MODELS"
for model in ${OLLAMA_MODELS//,/ }; do
  echo "    pulling $model ..."
  curl -sf "$OLLAMA_URL/api/pull" -d "{\"model\": \"$model\"}" > /dev/null \
    || echo "    WARNING: pull of $model failed (ollama unreachable?)"
done

echo "--- Provisioning AI servers (local Ollama + Fireworks)..."
drush scr /hackathon-scripts/provision-servers.php

echo "--- Discovering models..."
drush aip:discover-models local_ollama || echo "WARNING: ollama discovery failed"
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

drush cache:rebuild
echo "=== Setup complete ==="
echo "Routing decision log: /admin/reports/ai-router-decisions"
