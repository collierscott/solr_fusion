<?php

namespace Drupal\solr_fusion\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class SolrFusion Solr Query List Builder.
 */
class SolrFusionSolrQueryListBuilder extends ConfigEntityListBuilder {

  const MAX_DISPLAY_LENGTH = 30;

  /**
   * Create the header.
   *
   * @return array
   *   The header.
   */
  public function buildHeader() {
    $header['id'] = $this->t('id');
    $header['label'] = $this->t('Name');
    $header['bundle'] = $this->t('Bundle');
    $header['facet_list'] = $this->t('Facets');
    $header['field_list'] = $this->t('Field List');
    $header['sort'] = $this->t('Sort(s)');
    $header['boost_field'] = $this->t('Boost Field');
    $header['filter_query_list'] = $this->t('Filter Queries');
    $header['query_field_list'] = $this->t('Query Fields');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['bundle'] = $entity->bundle;
    $row['facet_list'] = $this->truncateDisplay($entity->facet_list);
    $row['field_list'] = $this->truncateDisplay($entity->field_list);
    $row['sort'] = $entity->sort;
    $row['boost_field'] = $this->truncateDisplay($entity->boost_field);
    $row['filter_query_list'] = $this->truncateDisplay($entity->filter_query_list);
    $row['query_field_list'] = $this->truncateDisplay($entity->query_field_list);
    return $row + parent::buildRow($entity);
  }

  /**
   * Truncate the displayed text.
   *
   * @param string $str
   *   The string to be truncated if needed.
   *
   * @return string
   *   The truncated string.
   */
  private function truncateDisplay(string $str) {
    if (strlen($str) > self::MAX_DISPLAY_LENGTH) {
      $str = substr($str, 0, self::MAX_DISPLAY_LENGTH) . '...';
    }

    return $str;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'solr_fusion';
  }

}
