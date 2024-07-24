<?php

namespace Drupal\Tests\solr_fusion\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\solr_fusion\Solr\SolrConnector;

/**
 * Tests for the SolrConnector.
 */
class SolrConnectorTest extends KernelTestBase {

  /**
   * Test if solr connection returns valid response.
   *
   * @param array $options
   *   The options.
   * @param mixed $expected
   *   The expected value.
   *
   * @dataProvider providerSolrConnectors
   */
  public function testSolrConnectorPingCore(array $options, $expected) {
    $solr = new SolrConnector($options);
    $result = $solr->pingCore();
    $this->assertIsNotBool($result, 'A connector results was received.');
  }

  /**
   * Provides data to test.
   *
   * @return array
   *   An array of tests.
   */
  public function providerSolrConnectors(): array {
    return [
      [
        [
          'scheme' => 'https',
          'host' => 'solr:solr-fusion@sandboxfusion.dev.solr-fusion.com',
          'port' => 8983,
          'path' => '/',
          'collection' => 'solr-fusioncms1',
          'core' => 'solr',
          'timeout' => 5,
          'index_timeout' => 5,
          'optimize_timeout' => 10,
          'finalize_timeout' => 30,
        ],
        FALSE,
      ],
      [
        [
          'scheme' => 'https',
          'host' => 'solr:solr-fusion@sandboxfusion.dev.solr-fusion.com',
          'port' => 8983,
          'path' => '/',
          'collection' => 'solr-fusioncms_videos',
          'core' => 'solr',
          'timeout' => 5,
          'index_timeout' => 5,
          'optimize_timeout' => 10,
          'finalize_timeout' => 30,
        ],
        FALSE,
      ],
    ];
  }

  /**
   * Modules to enable.
   *
   * @var array
   *   An array of modules needed.
   */
  protected static $modules = ['solr_fusion'];

}
