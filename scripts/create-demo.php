<?php
// Simple demo setup script - creates a basic page and sample content
// to showcase the agent in action.

use Drupal\node\Entity\Node;

echo "Creating demo content for hackathon...\n";

// Create a simple landing page explaining the agent
$node = Node::create([
  'type' => 'page',
  'title' => 'AMD Hackathon Token-Efficient Agent Demo',
  'body' => [
    'value' => '<p>This site demonstrates a hybrid token-efficient routing agent built with Drupal, ai_provider_universal, and ECA.</p>
<p>Submit tasks via the form or API. See routing decisions, model choices (local vs Fireworks), estimated savings, and factcheck results in real time.</p>
<p>JSON endpoint: /agent-decisions.json</p>',
    'format' => 'full_html',
  ],
  'status' => 1,
]);
$node->save();

echo "Demo page created.\n";
echo "Visit the site and use the agent!\n";
