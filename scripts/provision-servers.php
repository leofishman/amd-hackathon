<?php

/**
 * @file
 * Creates the AI server entities. Idempotent (drush scr).
 *
 * The Fireworks API key stays in the FIREWORKS_API_KEY env variable via the
 * Key module's env provider: it is never written to config or the database.
 */

$etm = \Drupal::entityTypeManager();

if (getenv('FIREWORKS_API_KEY')) {
  $keys = $etm->getStorage('key');
  if (!$keys->load('fireworks_api_key')) {
    $keys->create([
      'id' => 'fireworks_api_key',
      'label' => 'Fireworks API key',
      'key_type' => 'authentication',
      'key_provider' => 'env',
      'key_provider_settings' => [
        'env_variable' => 'FIREWORKS_API_KEY',
        'base64_encoded' => FALSE,
        'strip_line_breaks' => TRUE,
      ],
    ])->save();
    echo "Created key: fireworks_api_key (env provider)\n";
  }
}

// Optional API keys for the Content scan (web evidence + plagiarism),
// same env-provider pattern as Fireworks: never written to the database.
foreach (['TAVILY_API_KEY' => 'tavily_api_key', 'SERPER_API_KEY' => 'serper_api_key'] as $env => $key_id) {
  if (getenv($env) && !$etm->getStorage('key')->load($key_id)) {
    $etm->getStorage('key')->create([
      'id' => $key_id,
      'label' => str_replace('_', ' ', ucfirst($key_id)),
      'key_type' => 'authentication',
      'key_provider' => 'env',
      'key_provider_settings' => [
        'env_variable' => $env,
        'base64_encoded' => FALSE,
        'strip_line_breaks' => TRUE,
      ],
    ])->save();
    echo "Created key: $key_id (env provider)\n";
  }
}

$servers = $etm->getStorage('ai_universal_server');

// Track 3: AMD GPU server (ROCm + vLLM or ROCm + Ollama on minisforum).
// Set AMD_VLLM_URL to the OpenAI-compatible /v1 endpoint.
// This gives you the "AMD Instinct GPU" label + cost 0 in decisions.
if (($amd_url = getenv('AMD_VLLM_URL')) && !$servers->load('amd_vllm')) {
  $parts = parse_url(rtrim(preg_replace('~/v1/?$~', '', $amd_url), '/'));
  $servers->create([
    'id' => 'amd_vllm',
    'label' => 'AMD Instinct GPU (ROCm + Ollama/vLLM)',
    'backend' => 'openai_compatible',
    'host_name' => ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? $amd_url),
    'port' => (string) ($parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80)),
    'timeout' => 120,
  ])->save();
  echo "Created server: amd_vllm ($amd_url)\n";
}

if (!$servers->load('local_ollama')) {
  $servers->create([
    'id' => 'local_ollama',
    'label' => 'Local Ollama (zero cost)',
    'backend' => 'openai_compatible',
    'host_name' => 'http://ollama',
    'port' => '11434',
    'timeout' => 120,
  ])->save();
  echo "Created server: local_ollama\n";
}

if (getenv('FIREWORKS_API_KEY') && !$servers->load('fireworks')) {
  $servers->create([
    'id' => 'fireworks',
    'label' => 'Fireworks (remote)',
    'backend' => 'fireworks',
    'api_key' => 'fireworks_api_key',
    'timeout' => 120,
  ])->save();
  echo "Created server: fireworks\n";
}
