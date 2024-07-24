<?php

namespace Drupal\solr_fusion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines solr query parameters.
 *
 * @ConfigEntityType(
 *   id = "solr_fusion_facet",
 *   label = @Translation("Facet"),
 *   label_collection = @Translation("Facets"),
 *   label_singular = @Translation("facet"),
 *   label_plural = @Translation("facets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count facet",
 *     plural = "@count facets",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\solr_fusion\ListBuilder\SolrFusionSolrFacetListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\solr_fusion\Form\SolrFusionSolrFacetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "add" = "Drupal\solr_fusion\Form\SolrFusionSolrFacetForm"
 *     },
 *   },
 *   config_prefix = "solr_facet",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "service" = "service",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/solr_fusion/facets/{solr_fusion_facet}/edit",
 *     "delete-form" = "/admin/config/solr_fusion/facets/{solr_fusion_facet}/delete",
 *     "add-form" = "/admin/config/solr_fusion/facets/add",
 *     "collection" = "/admin/config/solr_fusion/facets",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "service",
 *     "field",
 *     "min_count",
 *     "limit",
 *   },
 * )
 */
class SolrFusionSolrFacet extends ConfigEntityBase {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The name.
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
   * The field value.
   *
   * @var string
   */
  public $field;

  /**
   * The min count value.
   *
   * @var int
   */
  public $min_count;

  /**
   * The limit.
   *
   * @var int
   */
  public $limit;

}
