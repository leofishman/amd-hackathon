<?php
/**
 * @file
 * Syncs Trusted Site nodes using Media Bias / Fact Check style ratings.
 *
 * This allows populating the reputation system with external bias/factual data
 * (from MediaBiasFactCheck.com or similar sources).
 *
 * Usage:
 *   drush scr /hackathon-scripts/sync-trusted-sites-from-mbfc.php
 *
 * You can edit data/mbfc-ratings-sample.json to add more sites.
 *
 * The script will:
 * - Create or update trusted_site nodes
 * - Set field_reputation based on factual + bias
 * - Append MBFC-style assessments
 */

use Drupal\node\Entity\Node;

$json_path = __DIR__ . '/../data/mbfc-ratings-sample.json';

if (!file_exists($json_path)) {
  echo "ERROR: $json_path not found.\n";
  exit(1);
}

$data = json_decode(file_get_contents($json_path), TRUE);

if (!is_array($data)) {
  echo "ERROR: Invalid JSON in $json_path\n";
  exit(1);
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

  if ($existing) {
    $node = reset($existing);
    $node->set('field_reputation', $reputation);

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
    'high' => 7,
    'mostly factual', 'mostly high' => 4,
    'mixed' => 0,
    'low', 'very low' => -5,
    default => 0,
  };

  $bias_adjustment = match (strtolower(trim($bias))) {
    'center' => 1,
    'left-center', 'right-center' => 0,
    'left', 'right' => -1,
    default => -2,
  };

  $score = $base + $bias_adjustment;

  // Clamp to -10 / +10
  return max(-10, min(10, $score));
}
