<?php

namespace Drupal\solr_fusion;

use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Solarium\Core\Client\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * A request handler interface.
 */
interface SolrFusionRequestHandlerInterface {

  /**
   * The number of rows to display.
   */
  const NUM_OF_ROWS = 10;

  /**
   * Get a response.
   *
   * @param string $queryId
   *   The query id to use.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $parameterBag
   *   The query parameters.
   * @param string $language
   *   The language code to use.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Solarium\Core\Client\Response
   *   Responses to return.
   */
  public function getResponse(string $queryId, ParameterBag $parameterBag, string $language): Response|JsonResponse;

  /**
   * Get the json response to send to the requesting client application.
   *
   * @param \Drupal\solr_fusion\SolrFusionSolrServiceInterface $service
   *   The service to use.
   * @param mixed $response
   *   The query response.
   * @param string $language
   *   The language.
   * @param string $defaultLanguage
   *   The default language.
   *
   * @return \Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function getJsonResponse(SolrFusionSolrServiceInterface $service, $response, string $language, string $defaultLanguage): SolrFusionSolrErrorResponse|JsonResponse;

  /**
   * Get the Solarium client request.
   *
   * @return \Solarium\Core\Client\Request
   *   The Solarium Client Request object.
   */
  public function getClientRequest(): Request;

  /**
   * Refresh content given the query id and the multiple urls.
   *
   * @param string $queryId
   *   The query id, ie refresh_index.
   * @param array $urls
   *   The list of relative urls to refresh.
   *
   * @return bool
   *   Return the TRUE of the job has started the refresh index process.
   */
  public function refreshIndex($queryId, array $urls): bool;

}
