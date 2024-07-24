<?php

namespace Drupal\solr_fusion\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SolrFusion Solr Json Response.
 */
abstract class SolrFusionSolrJsonResponse extends JsonResponse {

  /**
   * Log a message.
   *
   * @param string $message
   *   The message to log.
   * @param int $code
   *   The response code.
   *
   * @return string
   *   The message.
   */
  protected function createMessage($message, $code) {
    return sprintf('An error %s occurred - "%s"', $code, $message);
  }

}
