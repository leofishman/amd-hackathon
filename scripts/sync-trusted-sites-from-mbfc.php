<?php
/**
 * @file
 * Syncs Trusted Site nodes using Media Bias / Fact Check style ratings.
 *
 * Supports two formats in data/mbfc-ratings.json (or override $json_path):
 * 1. Simplified: [{domain, name, bias, factual, credibility, notes}, ...]
 * 2. Full MBFC export: entries with keys like "Source", "Source URL",
 *    "Factual Reporting", "Political Bias", "Credibility" etc.
 *
 * Usage:
 *   drush scr /hackathon-scripts/sync-trusted-sites-from-mbfc.php
 *
 * The script will:
 * - Create or update trusted_site nodes
 * - Set field_reputation based on factual + bias
 * - Append MBFC-style assessments
 *
 * data/mbfc-ratings.json ships pre-cleaned (dead/offline entries and
 * duplicate domains already removed — see git history for the filter).
 */

use Drupal\node\Entity\Node;

// getenv() returns FALSE (not NULL) when unset, so ?? alone won't fall
// through — check explicitly instead of chaining ??.
$json_path = $argv[1] ?? NULL;
if (!$json_path) {
  $json_path = getenv('MBFC_JSON') ?: NULL;
}
if (!$json_path) {
  $json_path = __DIR__ . '/../data/mbfc-ratings.json';
}

if (!file_exists($json_path)) {
  echo "ERROR: $json_path not found.\n";
  exit(1);
}

$data = json_decode(file_get_contents($json_path), TRUE);

if (!is_array($data)) {
  echo "ERROR: Invalid JSON in $json_path\n";
  exit(1);
}

// Support full MBFC dump (15k+ entries, keys like "Source", "Source URL", "Factual Reporting")
if (!empty($data) && isset($data[0]['Source']) && (isset($data[0]['Source URL']) || isset($data[0]['Factual Reporting']))) {
  echo "Detected full MBFC format (" . count($data) . " entries). Normalizing...\n";
  $normalized = [];
  foreach ($data as $raw) {
    $url = trim($raw['Source URL'] ?? '');
    if (!$url) continue;
    $domain = parse_url($url, PHP_URL_HOST) ?: $url;
    $domain = preg_replace('/^www\./', '', $domain);
    if (!$domain) continue;
    $normalized[] = [
      'domain' => strtolower($domain),
      'name' => $raw['Source'] ?? $domain,
      'bias' => $raw['Political Bias'] ?? $raw['Bias'] ?? 'Center',
      'factual' => $raw['Factual Reporting'] ?? 'Mixed',
      'credibility' => $raw['Credibility'] ?? 'Unknown',
      'notes' => trim(($raw['Bias'] ?? '') . ' ' . ($raw['Country'] ?? '') . ' ' . ($raw['Media Type'] ?? '')),
    ];
  }
  $data = $normalized;
  echo "Normalized to " . count($data) . " entries.\n";
}

$storage = \Drupal::entityTypeManager()->getStorage('node');

$created = 0;
$updated = 0;

foreach ($data as $site) {
  $domain = $site['domain'];
  $name = $site['name'] ?? $domain;

  // Calculate reputation from MBFC data
  $reputation = calculate_reputation_from_mbfc($site['factual'] ?? 'Mixed', $site['bias'] ?? 'Center');

  // Build assessment text
  $assessment = sprintf(
    "Source: Media Bias/Fact Check\nBias: %s | Factual: %s | Credibility: %s\n%s",
    $site['bias'] ?? 'Unknown',
    $site['factual'] ?? 'Unknown',
    $site['credibility'] ?? 'Unknown',
    $site['notes'] ?? ''
  );

  // Try to find existing node
  $existing = $storage->loadByProperties([
    'type' => 'trusted_site',
    'field_domain' => $domain,
  ]);

  $bias_value = mbfc_bias_to_field($site['bias'] ?? '');

  if ($existing) {
    $node = reset($existing);
    $node->set('field_reputation', $reputation);
    $node->set('field_bias', $bias_value);

    // Append if not already present
    $current = $node->get('field_assessments')->getValue();
    $has_mbfc = FALSE;
    foreach ($current as $item) {
      if (str_contains($item['value'] ?? '', 'Media Bias/Fact Check')) {
        $has_mbfc = TRUE;
        break;
      }
    }
    if (!$has_mbfc) {
      $current[] = ['value' => $assessment];
      $node->set('field_assessments', $current);
    }

    $node->save();
    $updated++;
    echo "Updated: $domain (rep $reputation)\n";
  }
  else {
    Node::create([
      'type' => 'trusted_site',
      'title' => $name,
      'field_domain' => $domain,
      'field_reputation' => $reputation,
      'field_bias' => $bias_value,
      'field_assessments' => [
        ['value' => $assessment],
      ],
      'status' => 1,
    ])->save();

    $created++;
    echo "Created: $domain (rep $reputation)\n";
  }
}

echo "\nDone. Created: $created, Updated: $updated\n";
echo "You can now view them at /admin/content?type=trusted_site\n";

/**
 * Simple mapping from MBFC data to our -10..+10 reputation scale.
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

  // Clamp to -10 / +10
  return max(-10, min(10, $score));
}

/**
 * MBFC bias label to the field_bias tokens coverage() understands
 * (left | lean_left | center | lean_right | right).
 */
function mbfc_bias_to_field(string $bias): string {
  return match (strtolower(trim($bias))) {
    'left', 'extreme left' => 'left',
    'left-center' => 'lean_left',
    'least biased', 'center', 'pro-science' => 'center',
    'right-center' => 'lean_right',
    'right', 'extreme right' => 'right',
    // MBFC "Questionable" rates credibility, not left/right leaning.
    default => '',
  };
}
