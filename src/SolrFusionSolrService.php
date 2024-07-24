<?php

namespace Drupal\solr_fusion;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\solr_fusion\Exception\InvalidArgumentException;
use Drupal\solr_fusion\Utils\ConfigurationBuilder;
use Drupal\taxonomy\Entity\Term;

/**
 * The Solr Fusion service.
 */
class SolrFusionSolrService implements SolrFusionSolrServiceInterface {

  /**
   * An array of configurations.
   *
   * @var array
   */
  private $configurations;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $logger) {
    $connectors = $configFactory->listAll('solr_fusion.solr_connector');
    $queryConnectors = $configFactory->listAll('solr_fusion.solr_query_connector');
    $parameters = $configFactory->listAll('solr_fusion.solr_query_parameter');
    $facets = $configFactory->listAll('solr_fusion.solr_facet');
    $queries = $configFactory->listAll('solr_fusion.solr_query.');
    $this->logger = $logger;

    $settings = $configFactory->get('solr_fusion.settings');
    $parameterConfigurations = ConfigurationBuilder::buildConfigurations($parameters);
    $facetConfigurations = ConfigurationBuilder::buildConfigurations($facets);
    $queryConfigurations = ConfigurationBuilder::buildConfigurations($queries);
    $connectorConfigurations = ConfigurationBuilder::buildConfigurations($connectors);
    $queryConnectorConfigurations = ConfigurationBuilder::buildConfigurations($queryConnectors);
    $recrawl_settings = $configFactory->get('solr_fusion.recrawl_settings');

    $this->configurations = [
      'settings' => $settings,
      'parameters' => $parameterConfigurations,
      'facets' => $facetConfigurations,
      'queries' => $queryConfigurations,
      'connectors' => $connectorConfigurations,
      'query_connectors' => $queryConnectorConfigurations,
      'recrawl_settings' => $recrawl_settings,
    ];

  }

  /**
   * Get the service to use for the query.
   *
   * @param string $queryId
   *   The query id from the url.
   *
   * @return \Drupal\solr_fusion\SolrFusionRequestHandlerInterface
   *   The response.
   */
  public function getHandlerToUse(string $queryId): SolrFusionRequestHandlerInterface {
    // Get the correct entry for the query connector.
    if (
      !$this->configurations['query_connectors'] ||
      count($this->configurations['query_connectors']) == 0 ||
      !array_key_exists($queryId, $this->configurations['query_connectors'])
    ) {
      throw new InvalidArgumentException('Bad Request. Query connector configuration not found.');
    }

    // Get the connector info from connectors using the query connector info.
    /** @var \Drupal\Core\Config\ImmutableConfig $queryConfiguration */
    $queryConnectors = $this->configurations['query_connectors'][$queryId];
    $connector = $queryConnectors->get('connector');
    $connections = $this->configurations['connectors'];
    $connection = $connections[$connector];

    if (!isset($connection)) {
      throw new InvalidArgumentException('Bad Request. Query connector configuration not found.');
    }

    $service = $connection->get('service');

    if ($service == 'solr') {
      return new SolrFusionSolrSolrRequestHandler($this->configurations, $this->logger);
    }
    elseif ($service == 'fusion') {
      return new SolrFusionSolrFusionRequestHandler($this->configurations, $this->logger);
    }
    else {
      throw new InvalidArgumentException('Bad Request. No request handler was found.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refreshContent(array $urls): void {
    try {
      /** @var \Drupal\Core\Config\ImmutableConfig $recrawlSettings */
      $recrawlSettings = $this->configurations['recrawl_settings'];
      $queryId = $recrawlSettings->get('query_id');
      if ($queryId) {
        $handler = $this->getHandlerToUse($queryId);
        $handler->refreshIndex($queryId, $urls);
      }
    }
    catch (InvalidArgumentException $e) {
      $this->logger->get('solr_fusion')->error('Lucidworks has failed to re-index the following url: @urls', [
        '@urls' => implode(', ', $urls),
      ]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTranslations(array $facets, string $language, string $defaultLanguage = 'en'): array {
    $translatedTerms = [];

    if (array_key_exists('facet_fields', $facets)) {
      $tids = [];

      foreach ($facets['facet_fields'] as $key => $field) {
        if ($key != $language) {
          if (is_array($field)) {
            foreach ($field as $id => $count) {
              $tids[] = $id;
            }
          }
        }
      }

      $terms = Term::loadMultiple($tids);
      $translatedTerms = [];

      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        if ($term->hasTranslation($language)) {
          $termData = $term->getTranslation($language);
          $languageUsed = $language;
        }
        else {
          $termData = $term;
          $languageUsed = $defaultLanguage;
        }

        $translatedTerms[] = [
          'id' => $termData->id(),
          'name' => $termData->getName(),
          'language' => $languageUsed,
        ];
      }
    }
    return $translatedTerms;

  }

  /**
   * Get settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Settings config.
   */
  public function getSettings() {
    return $this->configurations['settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function deleteDocument($document_path): array {
    // Uses the same account to refresh index to delete Solr documents.
    $queryId = 'refresh_index';
    $handler = $this->getHandlerToUse($queryId);
    return $handler->deleteDocument($queryId, $document_path);
  }

}
