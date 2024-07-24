<?php

namespace Drupal\solr_fusion\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SolrFusionHelpController.
 */
class SolrFusionSolrHelpController extends ControllerBase {

  /**
   * Request stack that controls the lifecycle of requests.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   *   The request information.
   */
  private $request;

  /**
   * Request stack that controls the lifecycle of requests.
   *
   * @var string
   *   The help text.
   */
  private $help;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\solr_fusion\Controller\SolrFusionSolrHelpController
   *   The controller
   */
  public static function create(ContainerInterface $container) {
    $request = \Drupal::request();
    $config = \Drupal::configFactory()->get('solr_fusion.help');
    return new static($request, $config);
  }

  /**
   * SolrFusionSolrController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration.
   */
  public function __construct(Request $request, ImmutableConfig $config) {
    $this->request = $request;
    $this->help = $config->get('help_txt');
  }

  /**
   * The help text.
   *
   * @return array
   *   The response.
   */
  public function help() {
    return [
      '#markup' => $this->help['value'],
    ];
  }

}
