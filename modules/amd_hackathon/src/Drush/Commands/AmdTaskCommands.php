<?php

namespace Drupal\amd_hackathon\Drush\Commands;

use Drupal\amd_hackathon\TaskRunner;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * CLI entry point for the scoring harness / local debugging.
 */
class AmdTaskCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(protected TaskRunner $runner) {
    parent::__construct();
  }

  /**
   * Runs one task through the hybrid smart route.
   */
  #[CLI\Command(name: 'amd:task')]
  #[CLI\Argument(name: 'task', description: 'The task text.')]
  #[CLI\Option(name: 'json', description: 'Output the full JSON payload instead of just the answer.')]
  #[CLI\Usage(name: 'drush amd:task "What is the capital of France?"', description: 'Run a task.')]
  public function task(string $task, array $options = ['json' => FALSE]): void {
    $result = $this->runner->run($task);
    if ($options['json']) {
      $this->output()->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    else {
      $this->output()->writeln($result['answer']);
      $routing = $result['routing'] ?? NULL;
      if ($routing) {
        $this->output()->writeln(sprintf(
          '[routing] model=%s complexity=%s est_tokens=%s cost=%s worst=%s (%d ms)',
          $routing['chosen_model'], $routing['complexity'], $routing['est_tokens'],
          $routing['est_cost'], $routing['est_cost_worst'], $result['elapsed_ms'],
        ));
      }
    }
  }

  /**
   * Imports or updates Trusted Sites using Media Bias/Fact Check style ratings.
   *
   * This is a convenience wrapper. The actual logic lives in:
   *   scripts/sync-trusted-sites-from-mbfc.php
   *
   * Recommended usage:
   *   drush scr scripts/sync-trusted-sites-from-mbfc.php
   *
   * You can extend data/mbfc-ratings-sample.json with more sites
   * from https://mediabiasfactcheck.com/
   */
  #[CLI\Command(name: 'factcheck:sync-mbfc')]
  public function syncMbfc(): void {
    $this->output()->writeln("Use: drush scr scripts/sync-trusted-sites-from-mbfc.php");
    $this->output()->writeln("Data file: data/mbfc-ratings-sample.json");
  }

}
