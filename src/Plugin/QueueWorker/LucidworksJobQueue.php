<?php

namespace Drupal\solr_fusion\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue items sent to run jobs in lucidworks.
 *
 * @QueueWorker(
 *  id = "lucidworks_job",
 *  title = @translation("Lucidworks Job Queue"),
 *  cron = {"time" = 120}
 * )
 */
final class LucidworksJobQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * Constructs a new lucidworksJob object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel interface.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr */
    $solr = \Drupal::service('solr_fusion.solr_service');
    $solr->refreshContent([$data['path']]);
    $this->logger->get('solr_fusion')->notice('User: @user has sent @path jobs to be index.', [
      '@user' => $data['user'],
      '@path' => $data['path'],
    ]);
  }

}
