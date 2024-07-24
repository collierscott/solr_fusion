<?php

namespace Drupal\solr_fusion;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\solr_fusion\EventDispatcher\Psr14EventDispatcher;
use Drupal\solr_fusion\Utils\ConfigurationBuilder;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Http;

/**
 * A handler for fusion requests.
 */
final class SolrFusionSolrFusionRequestHandler extends SolrFusionRequestHandler {

  /**
   * Create the client for the fusion service.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $connector
   *   The query id to use to get the client.
   * @param array $overrides
   *   Overrides to the connector data.
   *
   * @return \Solarium\Client|null
   *   The client.
   */
  protected function createClient(ImmutableConfig $connector, array $overrides = []): ?Client {
    $info = ConfigurationBuilder::getHostUsernamePassword($connector);
    $host = $info['username'] . ':' . $info['password'] . '@' . $info['host'];

    $path = $connector->get('path') . '/apps/' . $connector->get('app') . '/query-pipelines/' . $connector->get('core');
    if (!empty($overrides['path'])) {
      $path = $connector->get('path') . '/apps/' . $connector->get('app') . '/' . $overrides['path'];
    }

    $connector_data = [
      'scheme' => $connector->get('scheme'),
      'host' => $host,
      'port' => $connector->get('port'),
      'context' => 'collections',
      'path' => $path,
      'collection' => $connector->get('collection'),
      'core' => $connector->get('core'),
      'timeout' => (int) $connector->get('timeout'),
      'index_timeout' => $connector->get('index_timeout'),
      'optimize_timeout' => $connector->get('optimize_timeout'),
      'finalize_timeout' => $connector->get('finalize_timeout'),
    ];

    $clientConfiguration = [
      'endpoint' => [
        $connector->get('id') => $connector_data,
      ],
    ];
    return new Client(new Http(), new Psr14EventDispatcher(), $clientConfiguration);
  }

  /**
   * Get the type of the request handler.
   *
   * @return string
   *   The request handler type.
   */
  protected function getRequestHandlerType(): string {
    return 'fusion';
  }

}
