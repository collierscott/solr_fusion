<?php

namespace Drupal\solr_fusion\Solr;

/**
 * Interface SolrConnectorInterface.
 */
interface SolrConnectorInterface {

  /**
   * Pings the Solr core to tell whether it can be accessed.
   *
   * @param array $options
   *   (optional) An array of options.
   *
   * @return mixed
   *   The latency in milliseconds if the core can be accessed,
   *   otherwise FALSE.
   */
  public function pingCore(array $options = []);

}
