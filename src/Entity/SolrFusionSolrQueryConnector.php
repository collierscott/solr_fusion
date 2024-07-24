<?php

namespace Drupal\solr_fusion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Solr Fusion Query Connector config entity.
 *
 * @ConfigEntityType(
 *   id = "solr_fusion_query_connector",
 *   label = @Translation("Server Query Connector"),
 *   label_collection = @Translation("Server Query Connectors"),
 *   label_singular = @Translation("Server Query Connector"),
 *   label_plural = @Translation("Server Query Connectors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Server Query Connector",
 *     plural = "@count Server Query Connectors"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\solr_fusion\ListBuilder\SolrFusionSolrQueryConnectorListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\solr_fusion\Form\SolrFusionSolrQueryConnectorForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "add" = "Drupal\solr_fusion\Form\SolrFusionSolrQueryConnectorForm"
 *     },
 *   },
 *   config_prefix = "solr_query_connector",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *    "id" = "id",
 *    "label" = "label",
 *    "service" = "connector",
 *    "query" = "query",
 *    "uuid" = "uuid"
 *   },
 *   links = {
 *      "edit-form" = "/admin/config/solr_fusion/query_connector/{solr_fusion_query_connector}/edit",
 *      "delete-form" = "/admin/config/solr_fusion/query_connector/{solr_fusion_query_connector}/delete",
 *      "add-form" = "/admin/config/solr_fusion/query_connector/add",
 *      "collection" = "/admin/config/solr_fusion/query_connector"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "connector",
 *     "query"
 *   }
 * )
 */
class SolrFusionSolrQueryConnector extends ConfigEntityBase {

  /**
   * Connector Id.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The service to use.
   *
   * @var string
   */
  public $connector;

  /**
   * The query.
   *
   * @var string
   */
  public $query;

}
