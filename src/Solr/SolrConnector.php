<?php

namespace Drupal\solr_fusion\Solr;

use Drupal\solr_fusion\EventDispatcher\Psr14EventDispatcher;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Adapter\Http;
use Solarium\Core\Client\Endpoint;
use Solarium\Exception\HttpException;

/**
 * A Solr Connector.
 */
class SolrConnector implements SolrConnectorInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * A Solr Server.
   *
   * @var \Solarium\Client
   */
  private $solr;

  /**
   * The configuration for the connector.
   *
   * @var array
   */
  private $configuration;

  /**
   * SolrConnector constructor.
   *
   * @param array|null $configuration
   *   The configuration.
   */
  public function __construct(array $configuration = NULL) {
    $this->eventDispatcher = new Psr14EventDispatcher();

    if (!$configuration) {
      $merged = array_merge($this->defaultConfiguration(), $configuration);
      $this->setConfiguration($merged);
    }
    else {
      $this->setConfiguration($configuration);
    }
  }

  /**
   * A default configuration.
   *
   * @return array
   *   A default configuration.
   */
  public function defaultConfiguration() {
    return [
      'scheme' => 'http',
      'host' => 'localhost',
      'port' => 8983,
      'path' => '/',
      'core' => '',
      'timeout' => 5,
      'index_timeout' => 5,
      'optimize_timeout' => 10,
      'finalize_timeout' => 30,
    ];
  }

  /**
   * Set the configuration.
   *
   * @var array
   *   The configuration to set.
   */
  public function setConfiguration(array $configuration) {
    $configuration['port'] = (int) $configuration['port'];
    $configuration['timeout'] = (int) $configuration['timeout'];
    $configuration['index_timeout'] = (int) $configuration['index_timeout'];
    $configuration['optimize_timeout'] = (int) $configuration['optimize_timeout'];
    $configuration['finalize_timeout'] = (int) $configuration['finalize_timeout'];
    $this->configuration = $configuration;
  }

  /**
   * Connect to the client.
   */
  public function connect() {
    if (!$this->solr) {
      $configuration = $this->configuration;
      $this->solr = $this->createClient();
      $this->solr->createEndpoint($configuration + ['key' => 'solr_fusion'], TRUE);
    }
  }

  /**
   * Create a Client.
   *
   * @return \Solarium\Client
   *   The client.
   */
  protected function createClient() {
    $adapter = extension_loaded('curl') ? new Curl() : new Http();
    return new Client($adapter, $this->eventDispatcher);
  }

  /**
   * {@inheritDoc}
   */
  public function pingCore(array $options = []) {
    return $this->pingEndpoint(NULL, $options);
  }

  /**
   * Ping a Solr endpoint.
   *
   * @param \Solarium\Core\Client\Endpoint|null $endpoint
   *   The endpoint.
   * @param array $options
   *   The options.
   *
   * @return false|float
   *   The time if results is returned. Otherwise, false.
   */
  public function pingEndpoint(?Endpoint $endpoint = NULL, array $options = []) {
    $this->connect();

    $query = $this->solr->createPing();

    try {
      $start = microtime(TRUE);
      $result = $this->solr->execute($query, $endpoint);
      if ($result->getResponse()->getStatusCode() == 200) {
        // Add 1 µs to the ping time so we never return 0.
        return (microtime(TRUE) - $start) + 1E-6;
      }
    }
    catch (HttpException $e) {
      \Drupal::logger('solr_fusion')->warning('The function SolrConnector::pingEndpoint() was unable to ping the endpoint.');
    }

    return FALSE;
  }

}
