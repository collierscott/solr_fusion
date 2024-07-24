<?php

namespace Drupal\solr_fusion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Security\RequestSanitizer;
use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Drupal\solr_fusion\SolrFusionSolrSearchService;
use Drupal\solr_fusion\SolrFusionSolrServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller for the solr fusion module.
 */
final class SolrFusionSolrController extends ControllerBase {

  /**
   * Request stack that controls the lifecycle of requests.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   *   The request information.
   */
  private Request $request;

  /**
   * The Solr service.
   *
   * @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface
   */
  private SolrFusionSolrServiceInterface $solr;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private LoggerChannelFactoryInterface $logger;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\solr_fusion\Controller\SolrFusionSolrController
   *   The controller
   */
  public static function create(ContainerInterface $container): SolrFusionSolrController {
    $request = RequestSanitizer::sanitize(\Drupal::request(), [], TRUE);
    /** @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr */
    $solr = $container->get('solr_fusion.solr_service');
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = $container->get('language_manager');
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger */
    $logger = $container->get('logger.factory');
    return new SolrFusionSolrController($request, $solr, $languageManager, $logger);
  }

  /**
   * The constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr
   *   The solr service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel interface.
   */
  public function __construct(
    Request $request,
    SolrFusionSolrServiceInterface $solr,
    LanguageManagerInterface $languageManager,
    LoggerChannelFactoryInterface $logger,
  ) {
    $this->request = $request;
    $this->solr = $solr;
    $this->languageManager = $languageManager;
    $this->logger = $logger;
  }

  /**
   * Return the search results.
   *
   * @param string $query_id
   *   The id of the solr query.
   *
   * @return \Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   |\Symfony\Component\HttpFoundation\RedirectResponse
   *   The JSON response or solr not found response.
   */
  public function search(string $query_id): JsonResponse|SolrFusionSolrErrorResponse|RedirectResponse {
    $page_query_param = $this->request->query->get('page');
    if (!empty($page_query_param) && (!is_numeric($page_query_param) || $page_query_param < 0)) {
      $this->request->query->remove('page');
      $this->request->overrideGlobals();
      $url = $this->request->getUri();
      return new RedirectResponse($url, 302);
    }
    $searchService = new SolrFusionSolrSearchService($this->request, $this->solr, $this->languageManager, $this->logger);
    return $searchService->search($query_id);
  }

}
