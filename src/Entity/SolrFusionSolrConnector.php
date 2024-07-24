<?php

namespace Drupal\solr_fusion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Solr Fusion Connector config entity.
 *
 * @ConfigEntityType(
 *   id = "solr_fusion_connector",
 *   label = @Translation("Server Connector"),
 *   label_collection = @Translation("Server Connectors"),
 *   label_singular = @Translation("Server Connector"),
 *   label_plural = @Translation("Server Connectors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Server Connector",
 *     plural = "@count Server Connectors"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\solr_fusion\ListBuilder\SolrFusionSolrConnectorListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\solr_fusion\Form\SolrFusionSolrConnectorForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "add" = "Drupal\solr_fusion\Form\SolrFusionSolrConnectorForm"
 *     },
 *   },
 *   config_prefix = "solr_connector",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *    "id" = "id",
 *    "service" = "service",
 *    "label" = "label",
 *    "uuid" = "uuid"
 *   },
 *   links = {
 *      "edit-form" = "/admin/config/solr_fusion/connector/{solr_fusion_connector}/edit",
 *      "delete-form" = "/admin/config/solr_fusion/connector/{solr_fusion_connector}/delete",
 *      "add-form" = "/admin/config/solr_fusion/connector/add",
 *      "collection" = "/admin/config/solr_fusion/connector"
 *   },
 *   config_export = {
 *     "id",
 *     "service",
 *     "label",
 *     "scheme",
 *     "host",
 *     "port",
 *     "path",
 *     "signals_path",
 *     "collection",
 *     "app",
 *     "core",
 *     "username",
 *     "password",
 *     "timeout",
 *     "index_timeout",
 *     "optimize_timeout",
 *     "finalize_timeout"
 *   }
 * )
 */
class SolrFusionSolrConnector extends ConfigEntityBase {

  /**
   * Connector Id.
   *
   * @var string
   */
  protected $id;

  /**
   * The connector label.
   *
   * @var string
   */
  protected $label;

  /**
   * The service to use.
   *
   * @var string
   */
  public $service;

  /**
   * The connector status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The scheme.
   *
   * @var string
   */
  public $scheme;

  /**
   * The host.
   *
   * @var string
   */
  public $host;

  /**
   * The port.
   *
   * @var string
   */
  public $port;

  /**
   * The path.
   *
   * @var string
   */
  public $path;

  /**
   * The signals path.
   *
   * @var string
   */
  public $signals_path;

  /**
   * The collection.
   *
   * @var string
   */
  public $collection;

  /**
   * The app.
   *
   * @var string
   */
  public $app;

  /**
   * The core (solr)/pipeline (fusion).
   *
   * @var string
   */
  public $core;

  /**
   * The username.
   *
   * @var string
   */
  public $username;

  /**
   * The password.
   *
   * @var string
   */
  public $password;

  /**
   * The timeout.
   *
   * @var int
   */
  public $timeout;

  /**
   * The index timeout.
   *
   * @var int
   */
  public $index_timeout;

  /**
   * The optimized timeout.
   *
   * @var int
   */
  public $optimize_timeout;

  /**
   * The finalized timeout value.
   *
   * @var int
   */
  public $finalize_timeout;

}
