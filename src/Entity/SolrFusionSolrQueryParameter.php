<?php

namespace Drupal\solr_fusion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines solr query parameters.
 *
 * @ConfigEntityType(
 *   id = "solr_fusion_query_parameter",
 *   label = @Translation("Query Parameter"),
 *   label_collection = @Translation("Query Parameters"),
 *   label_singular = @Translation("query parameter"),
 *   label_plural = @Translation("query parameters"),
 *   label_count = @PluralTranslation(
 *     singular = "@count query parameter",
 *     plural = "@count query parameters",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\solr_fusion\ListBuilder\SolrFusionSolrQueryParameterListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\solr_fusion\Form\SolrFusionSolrQueryParameterForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "add" = "Drupal\solr_fusion\Form\SolrFusionSolrQueryParameterForm"
 *     },
 *   },
 *   config_prefix = "solr_query_parameter",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "service" = "service",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/solr_fusion/parameters/{solr_fusion_query_parameter}/edit",
 *     "delete-form" = "/admin/config/solr_fusion/parameters/{solr_fusion_query_parameter}/delete",
 *     "add-form" = "/admin/config/solr_fusion/parameters/add",
 *     "collection" = "/admin/config/solr_fusion/parameters",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "service",
 *     "value"
 *   },
 * )
 */
class SolrFusionSolrQueryParameter extends ConfigEntityBase {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The parameter name.
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
   * The parameter value.
   *
   * @var string
   */
  public $value;

}
