<?php
/**
 * @file
 * Enhanced demo content for minisforum AMD stack pitch.
 *
 * Adds:
 * - More corpus articles
 * - Additional student essays with different complexity profiles
 * - Makes cascade (local AMD → Fireworks) and trusted sites impact more visible
 *
 * Run after normal create-demo.php:
 *   drush scr /hackathon-scripts/create-enhanced-demo.php
 */

use Drupal\node\Entity\Node;

$exists = static function (string $title): bool {
  return (bool) \Drupal::entityQuery('node')
    ->condition('title', $title)
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();
};

// --- Additional corpus articles (expand evidence base)
$extra_corpus = [
  'Aldford University research output 2020-2025' =>
  'Between 2020 and 2025 Aldford University published 1,842 peer-reviewed papers. ' .
  'The Department of Materials Science accounted for 312 of them, with a field-weighted ' .
  'citation impact of 1.8. No papers from Aldford appear in the 2024 Nobel announcements.',

  'Campus sustainability report 2024' =>
  'The 2024 sustainability report states that Aldford University reduced scope 1 and 2 ' .
  'emissions by 34% since 2018. The university sources 62% of its electricity from ' .
  'renewables. The report makes no claim about being carbon negative.',
];

foreach ($extra_corpus as $title => $body) {
  if (!$exists($title)) {
    Node::create([
      'type' => 'article',
      'title' => $title,
      'body' => ['value' => $body, 'format' => 'basic_html'],
      'status' => 1,
    ])->save();
    echo "Extra corpus: $title\n";
  }
}

// --- Second essay: mostly supported claims (will mostly use local AMD, fewer contradictions)
$easy_essay = 'Student essay: Everyday life at Aldford University';
if (!$exists($easy_essay)) {
  Node::create([
    'type' => 'page',
    'title' => $easy_essay,
    'body' => [
      'value' => 'Life at Aldford University revolves around its three campuses. ' .
        'Students often mention the historic City campus and the modern Northgate medical campus. ' .
        'The engineering faculty remains the largest. Alumni frequently reference the Corvan Strait bridge ' .
        'designed by Elena Vasquez. The university has maintained operations through major historical events.',
      'format' => 'basic_html',
    ],
    'status' => 1,
  ])->save();
  echo "Easy essay: $easy_essay (mostly SUPPORTED)\n";
}

// --- Third essay: complex + fabricated + plagiarism bait (designed to trigger more verification / potential Fireworks escalation)
$complex_essay = 'Student essay: Aldford University and the future of technical education';
if (!$exists($complex_essay)) {
  Node::create([
    'type' => 'page',
    'title' => $complex_essay,
    'body' => [
      'value' => 'Founded in 1875, Aldford University quickly became a beacon of innovation. ' .
        'After the tragic 1953 fire that destroyed irreplaceable manuscripts, the institution rebuilt stronger than ever. ' .
        'Its researchers have contributed to breakthroughs comparable to those recognized by the Nobel committee. ' .
        'Today it operates multiple campuses and continues the grand tradition described by Darwin: ' .
        '"There is grandeur in this view of life, with its several powers, having been originally breathed into a few forms or into one..." ' .
        'The university embodies this endless evolution of knowledge.',
      'format' => 'basic_html',
    ],
    'status' => 1,
  ])->save();
  echo "Complex essay: $complex_essay (multiple fabrications + Darwin plagiarism)\n";
}

// --- Update landing page to explain the cascade and trusted sites
$landing_title = 'Drupal AI Factchecker — demo guide';
$landing = \Drupal::entityQuery('node')
  ->condition('title', $landing_title)
  ->accessCheck(FALSE)
  ->execute();
if ($landing) {
  $node = \Drupal::entityTypeManager()->getStorage('node')->load(reset($landing));
  $node->set('body', [
    'value' => '<p><strong>Minisforum local AMD stack (ROCm + Ollama)</strong> — full inference runs on university-owned AMD hardware with zero cloud dependency for most claims.</p>

<h3>How the cascade works</h3>
<ol>
  <li>Simple claims → routed to local AMD model (cost 0, fast, visible as <strong>amd_...</strong> in decisions log).</li>
  <li>Complex or high-stakes claims → escalate to premium model on Fireworks (dense Gemma) when the hybrid route decides it is necessary.</li>
</ol>

<h3>Trusted sites in action</h3>
<p>Reputation scores (positive for Nature, Reuters, AP; downgraded for Wikipedia) directly influence whether echoed information counts for or against a claim. Negative-reputation sites can mark a claim as "tainted".</p>

<h3>What to try</h3>
<ul>
  <li>Open the main "Student essay" and run Content scan — see the three fabricated claims (1875, lost manuscripts, Nobel) correctly identified as CONTRADICTED.</li>
  <li>Run the scan on the "Complex" and "Everyday life" essays to see different verdict patterns.</li>
  <li>Visit <strong>/admin/reports/ai-router-decisions</strong> — note the mix of local AMD and Fireworks calls.</li>
  <li>Check <strong>Trusted sites</strong> list (admin/content?type=trusted_site) to see editorial reputation decisions.</li>
</ul>

<p>Login: judge / (see submission credentials) or admin/admin locally.</p>',
    'format' => 'full_html',
  ]);
  $node->save();
  echo "Updated landing page with cascade + trusted sites explanation\n";
}

echo "Enhanced demo content created.\n";
