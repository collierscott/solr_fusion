<?php

namespace Drupal\solr_fusion\Solr;

use Solarium\Core\Query\QueryInterface;

/**
 * An interface for the Select Query.
 */
interface SolrFusionSolrQueryInterface extends QueryInterface {

  /**
   * Get the default parameters.
   *
   * @param bool $addToQuery
   *   Should the default parameters be added to the query.
   *
   * @return array
   *   The default parameters.
   */
  public function defaultParameters(bool $addToQuery = FALSE): array;

  /**
   * Add multiple parameters to the query.
   *
   * @param array $params
   *   The params to add.
   *
   * @return $this
   *   The query obj.
   */
  public function addParams(array $params): static;

  /**
   * Return the default values for 'fl'.
   *
   * @return array
   *   Aan array of terms for 'fl'
   */
  public function getDefaultFl(): array;

  /**
   * Add filters to the query from an array of key => values.
   *
   * @param array $filters
   *   An array of filters.
   * @param bool $useOrJoin
   *   Should use an OR join or not.
   */
  public function addFilters(array $filters, bool $useOrJoin = FALSE);

  /**
   * Add facets to the query.
   *
   * @param string $language
   *   The language.
   * @param string $configuration
   *   The configuration.
   * @param array $facets
   *   An array of facets configs.
   */
  public function addFacets(string $language, string $configuration, array $facets);

}
