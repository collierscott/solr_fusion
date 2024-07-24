<?php

namespace Drupal\solr_fusion;

use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The interface for the SolrFusion Solr Search Service.
 */
interface SolrFusionSolrSearchServiceInterface {

  /**
   * Return the search results.
   *
   * @param string $queryId
   *   The id of the solr query.
   *
   * @return \Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response or solr not found response.
   */
  public function search(string $queryId): JsonResponse|SolrFusionSolrErrorResponse;

}
