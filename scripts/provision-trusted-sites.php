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
// Updated with exact data from the full MBFC call JSON (~15k entries).
$editorial = [
  'reuters.com' => [NULL, ['Media Bias/Fact Check (full call): Least Biased, Very High factual reporting, High credibility']],
  'apnews.com' => [NULL, ['Media Bias/Fact Check (full call): Left-Center, High factual, High credibility']],
  'nature.com' => [NULL, ['Peer-reviewed; flagship multidisciplinary science journal']],
  'pubmed.ncbi.nlm.nih.gov' => [NULL, ['Indexes MEDLINE: journals vetted by the NLM Literature Selection Technical Review Committee']],
  'wikipedia.org' => [2, ['Site editorial decision (2026-07-06): open-edit model, low reliability for claim verification; kept as last-resort source only.']],
  'nytimes.com' => [NULL, ['Media Bias/Fact Check (full call): Left-Center, High factual, High credibility']],
  'theguardian.com' => [NULL, ['Media Bias/Fact Check (full call): Left-Center, High factual, High credibility']],
  'bbc.com' => [NULL, ['Media Bias/Fact Check (full call): Least Biased, Mostly Factual, High credibility']],
  'washingtonpost.com' => [NULL, ['Media Bias/Fact Check (full call): Left-Center, Mostly Factual, High credibility']],
  'breitbart.com' => [-5, ['Media Bias/Fact Check (full call): Questionable bias, Mixed factual, Low credibility']],

  // New for media bias / ideological tendency demo
  'foxnews.com' => [-5, [
    'Media Bias/Fact Check (full call 15k): Questionable bias, Low factual, Low credibility',
  ]],
  'rt.com' => [-6, [
    'Media Bias/Fact Check (full call 15k): Questionable bias, Very Low factual, Low credibility',
  ]],
  // Nota editorial (demo): No se asigna reputación positiva alta a grandes medios
  // de noticias legacy en este ejemplo. El módulo permite que cada institución
  // defina sus propios ratings según su criterio. Fuentes académicas u oficiales
  // suelen ser más seguras para reputación positiva.
];

/**
 * Reputation calculator (shared logic with sync script).
 */
function calculate_reputation_from_mbfc(string $factual, string $bias): int {
  $base = match (strtolower(trim($factual))) {
    'very high' => 8,
    'high' => 7,
    'mostly factual', 'mostly high' => 4,
    'mixed' => 0,
    'low', 'very low' => -5,
    default => 0,
  };

  $bias_adjustment = match (strtolower(trim($bias))) {
    'least biased', 'center' => 1,
    'left-center', 'right-center' => 0,
    'left', 'right' => -1,
    default => -2,
  };

  $score = $base + $bias_adjustment;
  return max(-10, min(10, $score));
}

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

// --- Also apply data from MBFC JSON if present (prefer filtered existing domains).
// This configures additional trusted sites (or overrides) using the external ratings.
// Supports full MBFC format or simplified.
$mbfc_json = __DIR__ . '/../data/mbfc-ratings-existing.json';
if (!file_exists($mbfc_json)) {
  $mbfc_json = __DIR__ . '/../data/mbfc-ratings-sample.json';
}
if (file_exists($mbfc_json)) {
  $mbfc_data = json_decode(file_get_contents($mbfc_json), true) ?: [];
  // Normalize full format if needed
  if (!empty($mbfc_data) && isset($mbfc_data[0]['Source']) && (isset($mbfc_data[0]['Source URL']) || isset($mbfc_data[0]['Factual Reporting']))) {
    $norm = [];
    foreach ($mbfc_data as $raw) {
      $url = trim($raw['Source URL'] ?? '');
      $domain = parse_url($url, PHP_URL_HOST) ?: $url;
      $domain = preg_replace('/^www\./', '', $domain);
      if ($domain) {
        $norm[] = [
          'domain' => strtolower($domain),
          'name' => $raw['Source'] ?? $domain,
          'bias' => $raw['Political Bias'] ?? $raw['Bias'] ?? 'Center',
          'factual' => $raw['Factual Reporting'] ?? 'Mixed',
          'credibility' => $raw['Credibility'] ?? 'Unknown',
          'notes' => trim(($raw['Bias'] ?? '') . ' ' . ($raw['Country'] ?? '') . ' ' . ($raw['Media Type'] ?? '')),
        ];
      }
    }
    $mbfc_data = $norm;
  }
  foreach ($mbfc_data as $site) {
    $domain = $site['domain'] ?? null;
    if (!$domain) continue;

    $reputation = calculate_reputation_from_mbfc($site['factual'] ?? 'Mixed', $site['bias'] ?? 'Center');

    $assessment = sprintf(
      "Source: Media Bias/Fact Check\nBias: %s | Factual: %s | Credibility: %s\n%s",
      $site['bias'] ?? 'Unknown',
      $site['factual'] ?? 'Unknown',
      $site['credibility'] ?? 'Unknown',
      $site['notes'] ?? ''
    );

    $existing = $storage->loadByProperties([
      'type' => 'trusted_site',
      'field_domain' => $domain,
    ]);

    if ($existing) {
      $node = reset($existing);
      $node->set('field_reputation', $reputation);

      $current = $node->get('field_assessments')->getValue();
      $has_mbfc = false;
      foreach ($current as $item) {
        if (str_contains($item['value'] ?? '', 'Media Bias/Fact Check')) {
          $has_mbfc = true;
          break;
        }
      }
      if (!$has_mbfc) {
        $current[] = ['value' => $assessment];
        $node->set('field_assessments', $current);
      }

      if (!$node->isPublished()) {
        $node->setPublished();
      }
      $node->save();
      echo "MBFC sync: Updated $domain (rep $reputation)\n";
    } else {
      // Create from JSON data if not present (e.g. additional sites)
      \Drupal\node\Entity\Node::create([
        'type' => 'trusted_site',
        'title' => $site['name'] ?? $domain,
        'field_domain' => $domain,
        'field_reputation' => $reputation,
        'field_assessments' => [['value' => $assessment]],
        'status' => 1,
      ])->save();
      echo "MBFC sync: Created $domain (rep $reputation)\n";
    }
  }
}

// (calculate_reputation_from_mbfc defined above for use in MBFC JSON sync block)
