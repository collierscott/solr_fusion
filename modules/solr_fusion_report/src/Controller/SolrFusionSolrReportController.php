<?php

namespace Drupal\solr_fusion_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Security\RequestSanitizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The SolrFusion Solr Report Controller.
 */
class SolrFusionSolrReportController extends ControllerBase {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\solr_fusion_report\Controller\SolrFusionSolrReportController
   *   The controller
   */
  public static function create(ContainerInterface $container): static {
    $request = RequestSanitizer::sanitize(\Drupal::request(), [], TRUE);

    return new static($request);
  }

  /**
   * The constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * Build the page.
   *
   * @return array
   *   The content array to render.
   */
  public function buildPage(): array {
    $formBuilder = $this->formBuilder();
    $query = $this->request->query;

    $searchForm = $formBuilder->getForm('\Drupal\solr_fusion_report\Form\SolrFusionSolrReportSearchForm');
    $listForm = [];

    if ($query->has('keys')) {
      $listForm = $formBuilder->getForm('\Drupal\solr_fusion_report\Form\SolrFusionSolrReportListingForm');
    }

    return [
      '#theme' => 'solr_fusion_report__search',
      '#content' => [
        'search_form' => $searchForm,
        'results_form' => $listForm,
      ],
    ];
  }

}
