<?php

namespace Drupal\solr_fusion\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * An error response.
 */
class SolrFusionSolrErrorResponse extends SolrFusionSolrJsonResponse {

  /**
   * Constructor.
   *
   * @param string $message
   *   The message to log and send.
   * @param int $code
   *   The response code.
   */
  public function __construct($message = '', $code = JsonResponse::HTTP_BAD_REQUEST) {
    \Drupal::logger('solr_fusion')->error($this->createMessage($message, $code));
    parent::__construct();
    return new JsonResponse([
      'statusCode' => $code,
      'statusMessage' => $message,
    ]);
  }

}
