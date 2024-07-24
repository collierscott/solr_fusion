<?php

namespace Drupal\solr_fusion\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * SolrFusion Solr Query Parameter List Builder.
 */
class SolrFusionSolrQueryParameterListBuilder extends ConfigEntityListBuilder {

  /**
   * Create the header.
   *
   * @return array
   *   The header.
   */
  public function buildHeader() {
    $header['id'] = $this->t('id');
    $header['service'] = $this->t('Service');
    $header['label'] = $this->t('Name');
    $header['value'] = $this->t('Value');

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
    $row['service'] = $entity->service;
    $row['label'] = $entity->label();
    $row['value'] = $entity->value;

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'solr_fusion';
  }

}
