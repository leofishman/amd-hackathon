<?php

namespace Drupal\amd_hackathon\Controller;

use Drupal\amd_hackathon\TaskRunner;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * JSON entry point for the scoring harness: POST /agent/task.
 *
 * Accepts {"task": "..."} (or a raw text body) and returns the answer with
 * full routing transparency. Re-map the in/out format here once the
 * kickoff reveals the harness contract.
 */
class TaskController extends ControllerBase {

  public function __construct(protected TaskRunner $runner) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get(TaskRunner::class));
  }

  /**
   * Handles POST /agent/task.
   */
  public function task(Request $request): JsonResponse {
    $body = (string) $request->getContent();
    $decoded = json_decode($body, TRUE);
    $task = is_array($decoded) ? (string) ($decoded['task'] ?? '') : trim($body);

    if ($task === '') {
      return new JsonResponse(['error' => 'Empty task. Send {"task": "..."}.'], 400);
    }

    try {
      return new JsonResponse($this->runner->run($task));
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

}
