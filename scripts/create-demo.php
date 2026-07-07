<?php

/**
 * @file
 * Track 3 demo content: a university corpus (evidence) + a student essay
 * whose fabricated claims contradict it. Idempotent by title.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

// Drupal 11.4's standard profile can install without content types
// (recipe-based); create what the demo needs, profile-independent.
if (!FieldStorageConfig::loadByName('node', 'body')) {
  FieldStorageConfig::create([
    'entity_type' => 'node',
    'field_name' => 'body',
    'type' => 'text_with_summary',
  ])->save();
  echo "Field storage: node.body\n";
}
$display_repo = \Drupal::service('entity_display.repository');
foreach (['article' => 'Article', 'page' => 'Basic page'] as $type_id => $label) {
  if (!NodeType::load($type_id)) {
    NodeType::create(['type' => $type_id, 'name' => $label])->save();
    echo "Content type: $type_id\n";
  }
  if (!FieldConfig::loadByName('node', $type_id, 'body')) {
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'body'),
      'bundle' => $type_id,
      'label' => 'Body',
    ])->save();
    $display_repo->getFormDisplay('node', $type_id)
      ->setComponent('body', ['type' => 'text_textarea_with_summary'])->save();
    $display_repo->getViewDisplay('node', $type_id)
      ->setComponent('body', ['type' => 'text_default'])->save();
    echo "Body field: $type_id\n";
  }
}

$exists = static function (string $title): bool {
  return (bool) \Drupal::entityQuery('node')
    ->condition('title', $title)
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();
};

// --- Evidence corpus: institutional knowledge, indexed as articles.
// The essay below contradicts these facts on purpose.
$corpus = [
  'History of Aldford University' =>
  'Aldford University was founded in 1892 by the industrialist Margaret
  Aldford as a technical college for railway engineering. It gained full
  university status in 1921. The university has never closed during its
  history, remaining open through both world wars by converting its
  workshops to medical supply production.',

  'The Aldford Library fire of 1953' =>
  'On 14 March 1953 a fire destroyed the east wing of the Aldford central
  library. Around 12,000 volumes were lost, but the rare manuscripts
  collection survived intact because it had been moved to the basement
  archive two years earlier. The east wing was rebuilt and reopened in 1957.',

  'Aldford University enrollment and campuses' =>
  'Aldford University enrolls approximately 24,000 students across three
  campuses: the historic City campus, the Riverside science campus opened
  in 1978, and the Northgate medical campus opened in 2003. Its largest
  faculty is Engineering, which accounts for roughly a third of enrollment.',

  'Notable Aldford alumni' =>
  'Notable alumni include the structural engineer Elena Vasquez (class of
  1961), who led the design of the Corvan Strait bridge, and the chemist
  Thomas Okafor (class of 1975), who received the Roswell Prize in 1998 for
  his work on polymer catalysis. No Aldford alumnus has ever won a Nobel
  Prize, a point of long-running campus humour.',
];

foreach ($corpus as $title => $body) {
  if (!$exists($title)) {
    Node::create([
      'type' => 'article',
      'title' => $title,
      'body' => ['value' => $body, 'format' => 'basic_html'],
      'status' => 1,
    ])->save();
    echo "Corpus: $title\n";
  }
}

// --- The student essay: plausible text with fabricated claims.
// CONTRADICTED by corpus: founding year (1875 vs 1892), manuscripts
// "tragically lost" (they survived), Nobel laureate (none exist).
// SUPPORTED by corpus: three campuses, Vasquez and the bridge.
$essay_title = 'Student essay: The legacy of Aldford University';
if (!$exists($essay_title)) {
  Node::create([
    'type' => 'page',
    'title' => $essay_title,
    'body' => [
      'value' => 'Aldford University, founded in 1875 by Margaret Aldford,
      stands among the oldest technical institutions in the country. Its
      resilience is legendary: even the devastating library fire of 1953,
      in which the entire rare manuscripts collection was tragically lost,
      could not halt its growth. Today the university operates three
      campuses and educates tens of thousands of students. Its alumni
      include the engineer Elena Vasquez, designer of the Corvan Strait
      bridge, and the physicist James Holloway, who won the Nobel Prize in
      Physics in 1989 for his discovery of quantum tunneling in
      semiconductors. Few institutions can claim such a record of
      unbroken achievement. '
      // Verbatim Darwin, unattributed: bait for the plagiarism check —
      // single line on purpose so the exact-phrase web search matches.
      . 'There is grandeur in this view of life, with its several powers, having been originally breathed into a few forms or into one; and that, whilst this planet has gone cycling on according to the fixed law of gravity, from so simple a beginning endless forms most beautiful and most wonderful have been, and are being, evolved.'
      . ' Aldford embodies that same endless evolution.',
      'format' => 'basic_html',
    ],
    'status' => 1,
  ])->save();
  echo "Essay: $essay_title\n";
}

// --- Landing page for judges (basic version — run create-enhanced-demo.php for full cascade + trusted sites explanation).
$landing_title = 'Drupal AI Factchecker — demo guide';
if (!$exists($landing_title)) {
  Node::create([
    'type' => 'page',
    'title' => $landing_title,
    'body' => [
      'value' => '<p><strong>Full AMD stack on local hardware (Minisforum + ROCm)</strong> — inference runs on your own AMD GPU with no external dependency for the majority of claims.</p>

<p>This site demonstrates claim-level content verification native to Drupal.</p>
<ol>
<li>Open the <em>Student essay</em> node and its <strong>Content scan</strong> tab.</li>
<li>Run the scan: claims are extracted and verified against the university\'s own indexed corpus.</li>
<li>Check <em>/admin/reports/ai-router-decisions</em> to see the routing cascade (local AMD for simple claims, escalation to premium models when needed).</li>
</ol>

<p>Run <code>drush scr /hackathon-scripts/create-enhanced-demo.php</code> to populate additional essays and the full explanation of the cascade + trusted sites.</p>

<p>Login: judge (see submission) or admin/admin locally.</p>',
      'format' => 'full_html',
    ],
    'status' => 1,
    'promote' => 1,
  ])->save();
  echo "Landing: $landing_title (basic — enhance with create-enhanced-demo.php)\n";
}
