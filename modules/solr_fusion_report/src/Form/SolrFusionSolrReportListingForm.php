<?php

namespace Drupal\solr_fusion_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Url;
use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Drupal\solr_fusion\SolrFusionSolrSearchService;
use Drupal\solr_fusion\SolrFusionSolrServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The SolrFusion Solr Report Listing Form.
 */
class SolrFusionSolrReportListingForm extends FormBase {

  /**
   * The Solr service.
   *
   * @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface
   */
  private SolrFusionSolrServiceInterface $solr;

  /**
   * A language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private LoggerChannelFactoryInterface $logger;

  /**
   * Service for pager information.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  private PagerManagerInterface $pagerManager;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\solr_fusion_report\Form\SolrFusionSolrReportListingForm
   *   The form.
   */
  public static function create(ContainerInterface $container): SolrFusionSolrReportListingForm {
    /** @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr */
    $solr = $container->get('solr_fusion.solr_service');
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = $container->get('language_manager');
    /** @var \Drupal\Core\logger\LoggerChannelFactoryInterface $logger */
    $logger = $container->get('logger.factory');
    /** @var \Drupal\Core\Pager\PagerManagerInterface $pagerManager */
    $pagerManager = $container->get('pager.manager');
    return new static($solr, $languageManager, $logger, $pagerManager);
  }

  /**
   * The constructor.
   *
   * @param \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr
   *   The solr service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel interface.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   Service for pager information.
   */
  public function __construct(
    SolrFusionSolrServiceInterface $solr,
    LanguageManagerInterface $languageManager,
    LoggerChannelFactoryInterface $logger,
    PagerManagerInterface $pager_manager,
  ) {
    $this->solr = $solr;
    $this->languageManager = $languageManager;
    $this->logger = $logger;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->getRequest();
    $query = $request->query;

    $queryId = 'admin_search';
    $numberPerPage = 100;

    $searchService = new SolrFusionSolrSearchService($request, $this->solr, $this->languageManager, $this->logger);
    $response = $searchService->search($queryId);

    if (
      $response->getStatusCode() != Response::HTTP_OK ||
      $response instanceof SolrFusionSolrErrorResponse
    ) {
      $form['markup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('An error occurred while trying to search with status code: ') . $response->getStatusCode(),
      ];
    }
    else {
      $pageNumber = $query->get('page') ?? 0;
      $content = $response->getContent();

      // Decode into an associative array.
      $decoded = json_decode($content, TRUE);

      $numFound = $decoded['body']['numFound'];
      $documents = $decoded['body']['docs'];
      $facetCounts = $decoded['facetCounts'];

      $bundleCounts = [];
      $sourceCounts = [];
      $contentLanguageCounts = [];

      if (
        array_key_exists('facet_fields', $facetCounts) &&
        array_key_exists('bundle', $facetCounts['facet_fields'])
      ) {
        $bundleCounts = $facetCounts['facet_fields']['bundle'];
      }

      if (
        array_key_exists('facet_fields', $facetCounts) &&
        array_key_exists('source', $facetCounts['facet_fields'])
      ) {
        $sourceCounts = $facetCounts['facet_fields']['source'];
      }

      if (
        array_key_exists('facet_fields', $facetCounts) &&
        array_key_exists('content_language', $facetCounts['facet_fields'])
      ) {
        $contentLanguageCounts = $facetCounts['facet_fields']['content_language'];
      }

      $showingMin = ($pageNumber * $numberPerPage) + 1;
      $showingMax = $showingMin + ($numberPerPage - 1);

      if ($showingMax > $numFound) {
        $showingMax = $numFound;
      }

      $form['metrics'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Showing @lower - @upper out of @total', [
          '@lower' => $showingMin,
          '@upper' => $showingMax,
          '@total' => $numFound,
        ]),
        '#prefix' => '<div class="solr-fusion-report-listing-table-metrics">',
        '#suffix' => '</div>',
      ];

      // Get the table information.
      $header = $this->getTableHeader();
      $output = $this->getList($documents, $numFound, $numberPerPage);

      // Remove `q` so it does not show in the pager links.
      $request->query->remove('q');

      $parameters = $query->all();
      $filters = array_key_exists('f', $parameters) ? $parameters['f'] : [];
      $keys = array_key_exists('keys', $parameters) ? $parameters['keys'] : '';
      $keyedFilters = [];

      foreach ($filters as $filter) {
        $keyedFilters[$filter] = $filter;
      }

      $form['table-data']['#prefix'] = '<div class="solr-fusion-report-listing-table-wrapper"><h3>' . $this->t('Filters') . '</h3>';
      $form['table-data']['#suffix'] = '</div>';

      $facetList = $this->buildFilterList('bundle', $bundleCounts, $keyedFilters, $keys);

      $form['table-data']['filters']['facets'] = [
        '#type' => 'item',
        '#title' => $this->t('<h3>@text</h3>', ['@text' => 'Content Types']),
        '#markup' => $facetList,
      ];

      $languageList = $this->buildFilterList('content_language', $contentLanguageCounts, $keyedFilters, $keys);

      $form['table-data']['filters']['languages'] = [
        '#type' => 'item',
        '#title' => $this->t('<h3>@text</h3>', ['@text' => 'Content Language']),
        '#markup' => $languageList,
      ];

      $sourceList = $this->buildFilterList('source', $sourceCounts, $keyedFilters, $keys);

      $form['table-data']['filters']['sources'] = [
        '#type' => 'item',
        '#title' => $this->t('<h3>@text</h3>', ['@text' => 'Source Site']),
        '#markup' => $sourceList,
      ];

      $form['table-data']['filters']['#prefix'] = '<div class="solr-fusion-report-listing-table-filters">';
      $form['table-data']['filters']['#suffix'] = '</div>';

      $form['table-data']['results']['#prefix'] = '<div class="solr-fusion-report-listing-table-results-wrapper">';
      $form['table-data']['results']['#suffix'] = '</div>';

      // Add a pager at the top.
      $form['table-data']['results']['pager-top'] = [
        '#type' => 'pager',
      ];

      $form['table-data']['results']['data'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $output,
        '#empty' => $this->t('No results found for the selections above.'),
        '#attributes' => [
          'class' => [
            'solr-fusion-report-listing-table',
          ],
        ],
        '#prefix' => '<div class="solr-fusion-report-listing-table-data">',
        '#suffix' => '</div>',
      ];

      // Add a pager at the bottom.
      $form['table-data']['results']['pager-bottom'] = [
        '#type' => 'pager',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This is a placeholder that is needed due to the interface.
  }

  /**
   * Get the form id.
   *
   * @return string
   *   The form id.
   */
  public function getFormId(): string {
    return 'solr_fusion_report_listing_form';
  }

  /**
   * Get list of the search results.
   *
   * @param array $documents
   *   An associative array of document data.
   * @param int $numFound
   *   The number of items found.
   * @param int $numberPerPage
   *   The number to display per page.
   *
   * @return array
   *   The options.
   */
  private function getList(array $documents, int $numFound, int $numberPerPage = 10): array {
    $output = [];

    if (!empty($documents)) {
      $this->pagerManager->createPager($numFound, $numberPerPage);

      foreach ($documents as $document) {
        $bundle = array_key_exists('bundle', $document) ? $document['bundle'] : '';
        $title = array_key_exists('title', $document) ? $document['title'] : '';
        $url = array_key_exists('url', $document) ? $document['url'] : '';

        $languages = '';

        if (array_key_exists('content_language', $document)) {
          // Languages should be all the languages ':' delimited.
          if (is_array($document['content_language'])) {
            $languages = implode(':', $document['content_language']);
          }
          else {
            $languages = $document['content_language'];
          }
        }

        $row = [
          'bundle' => $bundle,
          'langcode' => $languages,
          'title' => $title,
          'url' => $url,
        ];

        $output[] = $row;
      }
    }

    return $output;
  }

  /**
   * Table headers for the search results.
   *
   * @return array
   *   The headers.
   */
  private function getTableHeader(): array {
    return [
      'bundle' => $this->t('Content Type'),
      'langcode' => $this->t('Language'),
      'title' => $this->t('Title'),
      'url' => $this->t('Url'),
    ];
  }

  /**
   * Build a filter list.
   *
   * @param string $type
   *   The list type.
   * @param array $counts
   *   An array of items with count.
   * @param array $filters
   *   An array of parameters.
   * @param string $keys
   *   The keys parameter value.
   *
   * @return string
   *   Return the list markup.
   */
  private function buildFilterList(string $type, array $counts, array $filters, string $keys): string {
    ksort($counts);
    $list = '<ul class="facet-list">';

    foreach ($counts as $key => $count) {
      $updatedFilters = $filters;
      $isParameterValue = FALSE;
      $filter = $type . ':' . $key;

      if (array_key_exists($filter, $updatedFilters)) {
        $isParameterValue = TRUE;
        unset($updatedFilters[$filter]);
      }
      else {
        $updatedFilters[$filter] = $filter;
      }

      $updated['keys'] = $keys;
      $updated['f'] = [];

      if (!empty($updatedFilters)) {
        foreach ($updatedFilters as $filter) {
          $updated['f'][] = $filter;
        }
      }

      if (empty($updated['f'])) {
        unset($updated['f']);
      }

      if ($isParameterValue) {
        $url = Url::fromRoute('solr_fusion_report_search.form', $updated);
        $link = new Link('(-) ', $url);
        $list .= '<li>' . $link->toString() . $key . '</li>';
      }
      else {
        $url = Url::fromRoute('solr_fusion_report_search.form', $updated);
        $link = new Link($key . ' (' . $count . ')', $url);
        $list .= '<li>' . $link->toString() . '</li>';
      }
    }

    $list .= '</ul>';

    return $list;
  }

}
