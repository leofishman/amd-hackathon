<?php

/**
 * @file
 * Creates the hybrid smart route from discovered models. Idempotent.
 *
 * Run after aip:discover-models. Local models get cost 0 (they count as
 * zero tokens in the Track 1 scoring); the route then prefers them and only
 * escalates to Fireworks when the tier requires it.
 */

$etm = \Drupal::entityTypeManager();
$models = $etm->getStorage('ai_universal_model')->loadMultiple();

$candidates = [];
foreach ($models as $model) {
  if ($model->get('server_id') === 'local_ollama') {
    // Local inference is free in the hackathon scoring.
    if ($model->get('cost_input') !== 0.0 || $model->get('cost_output') !== 0.0) {
      $model->set('cost_input', 0);
      $model->set('cost_output', 0);
      $model->save();
    }
  }
  $ops = $model->get('operation_types') ?: $model->get('detected_operation_types') ?: [];
  if (in_array('chat', $ops, TRUE)) {
    $candidates[] = $model->id();
  }
}

if (!$candidates) {
  echo "WARNING: no chat models discovered; route not created.\n";
  return;
}

$routes = $etm->getStorage('ai_universal_route');
if (!$routes->load('hybrid_chat')) {
  $routes->create([
    'id' => 'hybrid_chat',
    'label' => 'Hybrid token-efficient chat',
    'operation_type' => 'chat',
    'candidates' => $candidates,
    // ponytail: tiers tuned blind; recalibrate at kickoff with real tasks.
    'simple_tier' => 1,
    'complex_tier' => 3,
    // Local model judging answers before return; rejection escalates to the
    // best candidate. Point it at a (fine-tuned) local Gemma at kickoff.
    'verifier_model' => getenv('AGENT_VERIFIER_MODEL') ?: '',
    'factcheck' => FALSE,
    'factcheck_min_score' => 0.75,
  ])->save();
  echo "Created route hybrid_chat with " . count($candidates) . " candidates:\n";
  foreach ($candidates as $c) {
    echo "  - $c\n";
  }
}
