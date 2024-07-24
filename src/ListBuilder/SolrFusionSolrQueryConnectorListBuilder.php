<?php

namespace Drupal\solr_fusion\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * The query connector list builder for the settings page.
 */
class SolrFusionSolrQueryConnectorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'solr_fusion';
  }

  /**
   * Create the header.
   *
   * @return array
   *   The header.
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('Id');
    $header['label'] = $this->t('Label');
    $header['connector'] = $this->t('Connector');
    $header['query'] = $this->t('Query');

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
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\solr_fusion\Entity\SolrFusionSolrQueryConnector $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['connector'] = $entity->connector;
    $row['query'] = $entity->query;

    return $row + parent::buildRow($entity);
  }

}
