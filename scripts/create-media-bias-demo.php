<?php
/**
 * @file
 * Demo to showcase ideological/media bias handling via Trusted Sites.
 *
 * Creates:
 * - Additional trusted sites with different reputations (Fox News, RT, etc.)
 * - Several "news articles" in the corpus representing different media framing.
 * - One main claim node (a report/analysis) to factcheck.
 *
 * Run with: drush scr /hackathon-scripts/create-media-bias-demo.php
 *
 * This demonstrates how reputation-weighted sources affect verdicts
 * and how the system can surface different media narratives.
 */

use Drupal\node\Entity\Node;

$exists = static function (string $title): bool {
  return (bool) \Drupal::entityQuery('node')
    ->condition('title', $title)
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();
};

// --- Use MBFC-style data for trusted sites (recommended approach)
// This demonstrates importing external bias ratings (e.g. from MediaBiasFactCheck.com)
// Run `drush factcheck:sync-mbfc` for a full import from data/mbfc-ratings-sample.json
//
// For the media bias demo we ensure the key contrasting sites exist.
$media_sites = [
  'foxnews.com' => [
    'reputation' => -3,
    'assessments' => [
      'Imported from Media Bias/Fact Check style data',
      'Right bias, Mixed factual reporting',
    ],
  ],
  'rt.com' => [
    'reputation' => -4,
    'assessments' => [
      'Imported from Media Bias/Fact Check style data',
      'Pro-Russian government perspective, often alternative narratives',
    ],
  ],
];

$storage = \Drupal::entityTypeManager()->getStorage('node');
foreach ($storage->loadByProperties(['type' => 'trusted_site']) as $node) {
  $domain = $node->get('field_domain')->value;
  if (isset($media_sites[$domain])) {
    $data = $media_sites[$domain];
    $node->set('field_reputation', $data['reputation']);
    if ($node->get('field_assessments')->isEmpty()) {
      $node->set('field_assessments', array_map(static fn (string $a) => ['value' => $a], $data['assessments']));
    }
    if (!$node->isPublished()) {
      $node->setPublished();
    }
    $node->save();
    echo "Updated trusted site: $domain (rep {$data['reputation']})\n";
  }
}

// --- Corpus articles representing different media coverage of the same event
$corpus_articles = [
  'Venezuela earthquake: Official report highlights building standards' => 
    'A 6.8 magnitude earthquake struck western Venezuela yesterday. ' .
    'Preliminary assessments indicate that many low-income residential buildings in the affected areas suffered major structural damage. ' .
    'Engineers point to age of construction and lack of modern seismic standards as contributing factors. ' .
    'Government sources emphasize rapid response and ongoing investigations into construction quality.',

  'Fox News coverage: Venezuela quake exposes regime failures' => 
    'The recent earthquake in Venezuela laid bare the consequences of years of socialist mismanagement. ' .
    'Numerous popular housing projects collapsed or were severely damaged, with experts attributing the destruction to shoddy construction and corruption. ' .
    'Critics say the Maduro government prioritized propaganda over proper building codes, leaving ordinary citizens vulnerable.',

  'RT report: Western media exaggerates Venezuela earthquake damage' => 
    'Mainstream outlets are amplifying claims about the Venezuela earthquake to attack the Bolivarian government. ' .
    'While some older buildings sustained damage, Venezuelan authorities report that the majority of structures held up remarkably well given the intensity. ' .
    'The focus on "poor construction" ignores the impact of long-term sanctions that limited access to quality materials.',

  'Reuters: Technical analysis of Venezuela earthquake damage' => 
    'Seismologists and structural engineers are examining why certain buildings performed poorly during the recent Venezuela earthquake. ' .
    'Initial findings suggest a combination of older construction techniques and variable enforcement of building regulations. ' .
    'The event has renewed calls for improved seismic resilience standards across the region.',
];

foreach ($corpus_articles as $title => $body) {
  if (!$exists($title)) {
    Node::create([
      'type' => 'article',
      'title' => $title,
      'body' => ['value' => $body, 'format' => 'basic_html'],
      'status' => 1,
    ])->save();
    echo "Media corpus: $title\n";
  }
}

// --- Main claim node to factcheck (a news analysis piece)
$main_claim_title = 'Analysis: What the Venezuela earthquake reveals about urban infrastructure';
if (!$exists($main_claim_title)) {
  Node::create([
    'type' => 'page',
    'title' => $main_claim_title,
    'body' => [
      'value' => 
        'Yesterday\'s earthquake in Venezuela caused widespread destruction, particularly in popular residential areas. ' .
        'Many buildings collapsed or were rendered uninhabitable. ' .
        'Experts and officials have attributed much of the damage to poor construction quality in low-income housing projects. ' .
        'This raises serious questions about building standards and oversight in the country.',
      'format' => 'basic_html',
    ],
    'status' => 1,
  ])->save();
  echo "Main claim node: $main_claim_title\n";
}

// --- Update landing page to include instructions for this demo
$landing_title = 'Drupal AI Factchecker — demo guide';
$landing_nodes = \Drupal::entityQuery('node')
  ->condition('title', $landing_title)
  ->accessCheck(FALSE)
  ->execute();

if ($landing_nodes) {
  $node = \Drupal::entityTypeManager()->getStorage('node')->load(reset($landing_nodes));
  $current_body = $node->get('body')->value;

  // Append new section if not already present
  if (strpos($current_body, 'Media bias and trusted sites demo') === false) {
    $new_section = '

<h3>Media coverage &amp; ideological tendency demo (new)</h3>
<p>Open the page <strong>"Analysis: What the Venezuela earthquake reveals about urban infrastructure"</strong> and run Content scan.</p>
<ul>
  <li>The corpus contains articles simulating coverage from different outlets (Fox News style, RT style, Reuters-style technical, official reports).</li>
  <li>Trusted sites can be populated from external bias rating sources (e.g. MediaBiasFactCheck.com) using <code>drush factcheck:sync-mbfc</code>.</li>
  <li>Observe how the system weighs evidence from high-reputation vs low-reputation sources.</li>
  <li>Check the decisions log and the per-claim evidence to see the effect of trusted/distrusted domains.</li>
</ul>
<p>This demonstrates how the reputation system can surface differences in how various media frame the same events.</p>';

    $node->set('body', ['value' => $current_body . $new_section, 'format' => 'full_html']);
    $node->save();
    echo "Landing page updated with media bias demo section\n";
  }
}

echo "Media bias / trusted sites demo content ready.\n";
echo "Run a Content scan on the new analysis node to see the effect.\n";
