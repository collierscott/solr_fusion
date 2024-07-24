<?php

namespace Drupal\solr_fusion\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * The connector list builder for the settings page.
 */
class SolrFusionSolrConnectorListBuilder extends ConfigEntityListBuilder {

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
    $header['id'] = $this->t('id');
    $header['service'] = $this->t('Service');
    $header['label'] = $this->t('Name');
    $header['scheme'] = $this->t('Scheme');
    $header['host'] = $this->t('Host');
    $header['port'] = $this->t('Port');
    $header['path'] = $this->t('Path');
    $header['signals_path'] = $this->t('Signals Path');
    $header['collection'] = $this->t('Collection');
    $header['core'] = $this->t('Core/Pipeline');
    $header['app'] = $this->t('App');
    $header['timeout'] = $this->t('Timeout');
    $header['index_timeout'] = $this->t('Index Timeout');
    $header['optimize_timeout'] = $this->t('Optimize Timeout');
    $header['finalize_timeout'] = $this->t('Finalize Timeout');

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
    /** @var \Drupal\solr_fusion\Entity\SolrFusionSolrConnector $entity */
    $row['id'] = $entity->id();
    $row['service'] = $entity->service;
    $row['label'] = $entity->label();
    $row['scheme'] = $entity->scheme;
    $row['host'] = $entity->host;
    $row['port'] = $entity->port;
    $row['path'] = $entity->path;
    $row['signals_path'] = $entity->signals_path;
    $row['collection'] = $entity->collection;
    $row['core'] = $entity->core;
    $row['app'] = $entity->app;
    $row['timeout'] = $entity->timeout;
    $row['index_timeout'] = $entity->index_timeout;
    $row['optimize_timeout'] = $entity->optimize_timeout;
    $row['finalize_timeout'] = $entity->finalize_timeout;

    return $row + parent::buildRow($entity);
  }

}
