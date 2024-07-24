<?php

namespace Drupal\solr_fusion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines solr query parameters.
 *
 * @ConfigEntityType(
 *   id = "solr_fusion_query",
 *   label = @Translation("Solr Query"),
 *   label_collection = @Translation("Solr Queries"),
 *   label_singular = @Translation("solr query"),
 *   label_plural = @Translation("solr queries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count solr query",
 *     plural = "@count solr queries",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\solr_fusion\ListBuilder\SolrFusionSolrQueryListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\solr_fusion\Form\SolrFusionSolrQueryForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "add" = "Drupal\solr_fusion\Form\SolrFusionSolrQueryForm"
 *     },
 *   },
 *   config_prefix = "solr_query",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/solr_fusion/solr_queries/{solr_fusion_query}/edit",
 *     "delete-form" = "/admin/config/solr_fusion/solr_queries/{solr_fusion_query}/delete",
 *     "add-form" = "/admin/config/solr_fusion/solr_queries/add",
 *     "collection" = "/admin/config/solr_fusion/solr_queries",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "sort",
 *     "boost_field",
 *     "field_list",
 *     "facet_list",
 *     "filter_query_list",
 *     "query_field_list",
 *     "bundle",
 *     "renamed_facet_field",
 *   },
 * )
 */
class SolrFusionSolrQuery extends ConfigEntityBase {
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
   * The sort.
   *
   * @var string
   */
  public $sort;

  /**
   * The boost field(s).
   *
   * @var string
   */
  public $boost_field;

  /**
   * The list of fields to be returned.
   *
   * @var string
   */
  public $field_list;

  /**
   * The list of facets to be used as parameters.
   *
   * @var string
   */
  public $facet_list;

  /**
   * A list of filter queries.
   *
   * @var string
   */
  public $filter_query_list;

  /**
   * A list of query fields.
   *
   * @var string
   */
  public $query_field_list;

  /**
   * Bundle.
   *
   * @var string
   */
  public $bundle;

  /**
   * The rename facet field.
   *
   * @var string
   */
  public $renamed_facet_field;

}
