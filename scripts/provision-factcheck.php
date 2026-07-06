<?php

/**
 * @file
 * Provisions the Track 3 factcheck demo: evidence index + settings. Idempotent.
 *
 * Run after provision-servers.php and model discovery. Points the factcheck
 * extractor/checker at the AMD vLLM server when available, otherwise at the
 * local Ollama model, and wires the evidence index + optional API keys.
 */

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;

$etm = \Drupal::entityTypeManager();

// --- Evidence index (Search API, database backend, Highlight excerpts).
if (!Server::load('evidence')) {
  Server::create([
    'id' => 'evidence',
    'name' => 'Evidence (database)',
    'backend' => 'search_api_db',
    'backend_config' => ['database' => 'default:default'],
  ])->save();
  echo "Created search server: evidence\n";
}

if (!Index::load('evidence_corpus')) {
  Index::create([
    'id' => 'evidence_corpus',
    'name' => 'Evidence corpus',
    'server' => 'evidence',
    'datasource_settings' => [
      'entity:node' => [
        'bundles' => ['default' => FALSE, 'selected' => ['article']],
        'languages' => ['default' => TRUE, 'selected' => []],
      ],
    ],
    'field_settings' => [
      'title' => [
        'label' => 'Title',
        'datasource_id' => 'entity:node',
        'property_path' => 'title',
        'type' => 'text',
        'boost' => 2.0,
      ],
      'body' => [
        'label' => 'Body',
        'datasource_id' => 'entity:node',
        'property_path' => 'body',
        'type' => 'text',
      ],
    ],
    'processor_settings' => [
      'add_url' => [],
      'rendered_item' => [],
      // Excerpts are what the checker reads on a database backend.
      'highlight' => [
        'highlight' => 'always',
        'highlight_partial' => FALSE,
        'excerpt' => TRUE,
        'excerpt_always' => TRUE,
        'excerpt_length' => 512,
        'exclude_fields' => [],
        'prefix' => '<strong>',
        'suffix' => '</strong>',
      ],
    ],
    'options' => ['cron_limit' => 50, 'index_directly' => TRUE],
  ])->save();
  echo "Created evidence index: evidence_corpus\n";
}

// --- Pick extractor/checker models: prefer the AMD server.
$models = $etm->getStorage('ai_universal_model')->loadMultiple();
$pick = static function (string $server_id) use ($models): string {
  foreach ($models as $model) {
    $ops = $model->get('operation_types') ?: $model->get('detected_operation_types') ?: [];
    if ($model->get('server_id') === $server_id && in_array('chat', $ops, TRUE)) {
      return $model->id();
    }
  }
  return '';
};
// Prefer AMD GPU server (minisforum ROCm Ollama or notebook vLLM) when
// AMD_VLLM_URL is set. Falls back to the first OLLAMA_MODELS entry.
$wanted = '';
if ($raw = explode(',', (string) getenv('OLLAMA_MODELS'))[0] ?? '') {
  $wanted_id = 'local_ollama__' . preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($raw)));
  $wanted = isset($models[$wanted_id]) ? $wanted_id : '';
}
$checker = $pick('amd_vllm') ?: $wanted ?: $pick('local_ollama');
if (!$checker) {
  echo "WARNING: no chat model found for factcheck; settings left as-is.\n";
  return;
}

$settings = \Drupal::configFactory()->getEditable('ai_provider_universal_factcheck.settings');
$settings
  ->set('checker_model', $checker)
  ->set('extractor_model', $checker)
  ->set('evidence_index', 'evidence_corpus')
  ->set('max_claims', 12);
if (getenv('TAVILY_API_KEY')) {
  $settings->set('tavily_key', 'tavily_api_key');
}
if (getenv('SERPER_API_KEY')) {
  $settings->set('plagiarism_key', 'serper_api_key');
}
$settings->save();
echo "Factcheck configured: checker/extractor = $checker, index = evidence_corpus\n";
