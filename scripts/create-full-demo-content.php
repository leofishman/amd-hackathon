<?php
/**
 * @file
 * Creates rich demo content showcasing all factcheck features.
 *
 * Run: drush scr /hackathon-scripts/create-full-demo-content.php
 *
 * Features demonstrated:
 * - Local corpus evidence
 * - Web evidence with trusted sites
 * - Reputation and bias effects (tainted, blindspots)
 * - Different claim complexities
 * - Media bias examples (Fox, RT, Reuters style)
 */

use Drupal\node\Entity\Node;

$exists = static function (string $title) {
  return (bool) \Drupal::entityQuery('node')
    ->condition('title', $title)
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();
};

// 1. Core Aldford (already in create-demo, but ensure)
$corpus = [
  'History of Aldford University' => 'Aldford University was founded in 1892...',
  // ... (shortened for demo)
];

foreach ($corpus as $title => $body) {
  if (!$exists($title)) {
    Node::create([
      'type' => 'article',
      'title' => $title,
      'body' => ['value' => $body, 'format' => 'basic_html'],
      'status' => 1,
    ])->save();
  }
}

// 2. Media bias example (Venezuela quake)
$media_corpus = [
  'Venezuela earthquake damage report (Reuters style)' => 
    'A 6.8 magnitude earthquake struck Venezuela. Engineers attribute damage in low-income areas to poor construction standards and lack of maintenance.',
  'Fox News: Venezuela quake exposes socialist failures' => 
    'The earthquake revealed the total failure of the Maduro regime. Buildings collapsed due to years of corruption and incompetence.',
  'RT: Western media exaggerates Venezuela earthquake' => 
    'US-backed media is using the earthquake to attack Venezuela while ignoring the real causes: sanctions and interference.',
];

foreach ($media_corpus as $title => $body) {
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

// 3. Main claim to scan
$claim_title = 'Report: Venezuela earthquake destroyed many buildings due to poor construction';
if (!$exists($claim_title)) {
  Node::create([
    'type' => 'page',
    'title' => $claim_title,
    'body' => [
      'value' => 'Yesterday\'s earthquake in Venezuela caused massive destruction in popular neighborhoods. Experts say many buildings collapsed because they were poorly built and not up to seismic standards.',
      'format' => 'basic_html',
    ],
    'status' => 1,
  ])->save();
  echo "Main claim: $claim_title\n";
}

// 4. Simple supported claim (local corpus heavy)
$simple_title = 'Aldford University has three campuses';
if (!$exists($simple_title)) {
  Node::create([
    'type' => 'page',
    'title' => $simple_title,
    'body' => ['value' => 'Aldford University operates three campuses: City, Riverside, and Northgate.', 'format' => 'basic_html'],
    'status' => 1,
  ])->save();
}

// Update landing with feature list
$landing_title = 'Drupal AI Factchecker — demo guide';
if ($exists($landing_title)) {
  $node = \Drupal::entityTypeManager()->getStorage('node')->load(
    array_keys(\Drupal::entityQuery('node')->condition('title', $landing_title)->accessCheck(FALSE)->execute())[0]
  );
  $body = '<h2>Full Feature Demo Content</h2>
<p>This site demonstrates multiple aspects of the factchecker:</p>
<ul>
  <li><strong>Local corpus verification</strong>: Claims checked against Aldford University articles (use the simple campus claim).</li>
  <li><strong>Trusted sites & bias</strong>: Run scan on the Venezuela earthquake report. See how Fox/RT style sources vs Reuters affect the verdict.</li>
  <li><strong>Reputation impact</strong>: Negative reputation sites can mark claims as "tainted".</li>
  <li><strong>Bias spread</strong>: The discrepancy analysis shows the ideological mix of sources.</li>
  <li><strong>Hybrid routing</strong>: Check /admin/reports/ai-router-decisions for model choices (local AMD vs Fireworks).</li>
</ul>
<p>Run Content scan on different nodes to see the full power.</p>';

  $node->set('body', ['value' => $body, 'format' => 'full_html']);
  $node->save();
  echo "Landing updated with feature explanation\n";
}

echo "Full demo content created.\n";