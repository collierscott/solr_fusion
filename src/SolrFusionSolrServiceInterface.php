<?php

namespace Drupal\solr_fusion;

/**
 * The SOLR Fusion service interface.
 */
interface SolrFusionSolrServiceInterface {

  /**
   * Get the service to use for the query.
   *
   * @param string $queryId
   *   The query id from the url.
   *
   * @return \Drupal\solr_fusion\SolrFusionRequestHandlerInterface
   *   The response.
   */
  public function getHandlerToUse(string $queryId): SolrFusionRequestHandlerInterface;

  /**
   * Get taxonomy term translations.
   *
   * @param array $facets
   *   Some facets.
   * @param string $language
   *   The language to translate into.
   * @param string $defaultLanguage
   *   The default language.
   *
   * @return array
   *   An array of terms.
   */
  public function getTranslations(array $facets, string $language, string $defaultLanguage = 'en'): array;

  /**
   * Get settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Settings config.
   */
  public function getSettings();

  /**
   * Refresh content of the urls provided.
   *
   * @param array $urls
   *   A list of urls to refresh.
   */
  public function refreshContent(array $urls): void;

  /**
   * Delete document from solr index.
   *
   * @param string $document_path
   *   The url to delete.
   *
   * @return array
   *   Array of items with status_code.
   */
  public function deleteDocument($document_path): array;

}
