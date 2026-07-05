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

$servers = $etm->getStorage('ai_universal_server');

if (!$servers->load('local_ollama')) {
  $servers->create([
    'id' => 'local_ollama',
    'label' => 'Local Ollama (zero cost)',
    'backend' => 'openai_compatible',
    'host_name' => 'http://ollama',
    'port' => '11434',
    'timeout' => 60,
  ])->save();
  echo "Created server: local_ollama\n";
}

if (getenv('FIREWORKS_API_KEY') && !$servers->load('fireworks')) {
  $servers->create([
    'id' => 'fireworks',
    'label' => 'Fireworks (remote)',
    'backend' => 'fireworks',
    'api_key' => 'fireworks_api_key',
    'timeout' => 60,
  ])->save();
  echo "Created server: fireworks\n";
}
