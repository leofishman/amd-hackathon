<?php

/**
 * @file
 * Applies the module's trusted-sites recipes and publishes the seeds.
 *
 * The seeds ship UNPUBLISHED because reputation is an editorial decision;
 * for this demo we adopt the module's suggested reputations as-is.
 * Idempotent: recipes skip existing config, publishing skips published.
 */

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;

$base = '/opt/drupal/web/modules/contrib/ai_provider_universal/recipes';
foreach (['factcheck_trusted_sites', 'factcheck_trusted_sites_seeds'] as $r) {
  try {
    RecipeRunner::processRecipe(Recipe::createFromDirectory("$base/$r"));
    echo "Recipe applied: $r\n";
  }
  catch (\Throwable $e) {
    echo "Recipe $r skipped: {$e->getMessage()}\n";
  }
}

// Demo editorial decisions: assessments backing each reputation, and the
// site owner's own calls (Wikipedia downgraded, with the reasoning cited).
$editorial = [
  'reuters.com' => [NULL, ['Media Bias/Fact Check: Least Biased, Very High factual reporting', 'NewsGuard: high credibility score', 'Public corrections policy (Standards & Values)']],
  'apnews.com' => [NULL, ['Media Bias/Fact Check: Least Biased, Very High factual reporting', 'Public corrections policy']],
  'nature.com' => [NULL, ['Peer-reviewed; flagship multidisciplinary science journal']],
  'pubmed.ncbi.nlm.nih.gov' => [NULL, ['Indexes MEDLINE: journals vetted by the NLM Literature Selection Technical Review Committee']],
  'wikipedia.org' => [2, ['Site editorial decision (2026-07-06): open-edit model, low reliability for claim verification; kept as last-resort source only.']],

  // New for media bias / ideological tendency demo
  'foxnews.com' => [-3, [
    'Media Bias/Fact Check: Right bias, Mixed factual reporting',
    'Editorial note: Frequently frames international stories through conservative/anti-socialist lens.',
  ]],
  'rt.com' => [-4, [
    'Known for pro-Russian government perspective on international affairs',
    'Often provides alternative framing that challenges Western mainstream narratives.',
  ]],
  // Nota editorial (demo): No se asigna reputación positiva alta a grandes medios
  // de noticias legacy en este ejemplo. El módulo permite que cada institución
  // defina sus propios ratings según su criterio. Fuentes académicas u oficiales
  // suelen ser más seguras para reputación positiva.
];

$storage = \Drupal::entityTypeManager()->getStorage('node');
foreach ($storage->loadByProperties(['type' => 'trusted_site']) as $node) {
  $domain = $node->get('field_domain')->value;
  if (isset($editorial[$domain])) {
    [$reputation, $assessments] = $editorial[$domain];
    if ($reputation !== NULL) {
      $node->set('field_reputation', $reputation);
    }
    if ($node->get('field_assessments')->isEmpty()) {
      $node->set('field_assessments', array_map(static fn (string $a) => ['value' => $a], $assessments));
    }
  }
  if (!$node->isPublished()) {
    $node->setPublished();
  }
  $node->save();
  echo "Trusted site ready: $domain (rep {$node->get('field_reputation')->value})\n";
}
