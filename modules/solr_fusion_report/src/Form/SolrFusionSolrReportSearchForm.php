<?php

namespace Drupal\solr_fusion_report\Form;

use Drupal\Core\Config\StorageException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\file\FileRepositoryInterface;
use Drupal\solr_fusion\SolrFusionSolrSearchService;
use Drupal\solr_fusion\SolrFusionSolrServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The SolrFusion Solr Report Search Form.
 */
class SolrFusionSolrReportSearchForm extends FormBase {

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
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  private StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * File system handler.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * File entity repository handler.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  private FileRepositoryInterface $fileRepository;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\solr_fusion_report\Form\SolrFusionSolrReportListingForm
   *   The form.
   */
  public static function create(ContainerInterface $container): SolrFusionSolrReportSearchForm {
    /** @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr */
    $solr = $container->get('solr_fusion.solr_service');
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = $container->get('language_manager');
    /** @var \Drupal\Core\logger\LoggerChannelFactoryInterface $logger */
    $logger = $container->get('logger.factory');
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager */
    $streamWrapperManager = $container->get('stream_wrapper_manager');
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = $container->get('file_system');
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = $container->get('file.repository');

    return new static(
      $solr,
      $languageManager,
      $logger,
      $streamWrapperManager,
      $fileSystem,
      $fileRepository
    );
  }

  /**
   * The constructor.
   *
   * @param \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr
   *   The solr service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel interface.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file entity repository.
   */
  public function __construct(
    SolrFusionSolrServiceInterface $solr,
    LanguageManagerInterface $language_manager,
    LoggerChannelFactoryInterface $logger,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    FileSystemInterface $file_system,
    FileRepositoryInterface $file_repository,
  ) {
    $this->solr = $solr;
    $this->languageManager = $language_manager;
    $this->logger = $logger;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $parameters = $this->getRequest()->query;
    $keys = $parameters->has('keys') ? $parameters->get('keys') : '';

    $form['#attributes'] = [
      'class' => [
        'container-inline',
      ],
    ];

    $form['keys'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Search Terms'),
      '#size' => 30,
      '#default_value' => $keys,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    // Add a reset button.
    $form['actions']['reset'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('solr_fusion_report_search.form'),
      '#title' => $this->t('Reset'),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];

    if ($parameters->has('keys')) {
      $form['actions']['export'] = [
        '#type' => 'submit',
        '#value' => $this->t('Export Results'),
        '#submit' => [[$this, 'exportResults']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $query = [];

    if (array_key_exists('keys', $values)) {
      $query['keys'] = $values['keys'];
    }

    $form_state->setRedirect('solr_fusion_report_search.form', [], ['query' => $query]);
  }

  /**
   * {@inheritdoc}
   */
  public function exportResults(array &$form, FormStateInterface $form_state):void {

    $request = $this->getRequest();
    $filename = date('U') . "_BulkTaggingReport.csv";

    // Determine if the export file should be stored in the public or private
    // file system.
    $directory = ($this->streamWrapperManager->isValidScheme('private') ? 'private://' : 'public://') . 'solr_fusion_report/';

    try {
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      $destination = $directory . $filename;
      $file = $this->fileRepository->writeData('', $destination, FileSystemInterface::EXISTS_REPLACE);

      if (!$file) {
        // Failed to create the file, abort the batch.
        throw new StorageException('Could not create a temporary file.');
      }

      $file->setTemporary();
      $file->save();
    }
    catch (StorageException $e) {
      $message = $this->t('Could not write to temporary output file for result export (@file). Check permissions.', ['@file' => $destination]);
      $this->logger('solr_fusion_report')->error($message);
    }

    $keys = $request->query->get('keys');
    $queryId = 'admin_search';

    $searchService = new SolrFusionSolrSearchService($request, $this->solr, $this->languageManager, $this->logger);
    $response = $searchService->search($queryId);

    if ($response->getStatusCode() == Response::HTTP_OK) {
      $output = fopen($destination, 'a');

      $headers = [
        'Content Type',
        'Language',
        'Title',
        'Url',
      ];

      // Add the header as the first line of the CSV.
      fputcsv($output, $headers);

      // Decode into an associative array.
      $decoded = json_decode($response->getContent(), TRUE);
      $numFound = $decoded['body']['numFound'];

      // Get number of pages based on total records from response.
      $pages = ($numFound / 100);

      // Loop through the pages to recall SOLR endpoint and capture data.
      for ($page = 0; $page < $pages; $page++) {
        $operations[] = [
          'Drupal\solr_fusion_report\BatchService::exportSolrDataProcess',
          [
            $keys,
            $page,
            $destination,
            $queryId,
            $numFound,
          ],
        ];
      }

      // Create Batch process for fetching Solr results and saving to CSV.
      $batch = [
        'title' => $this->t('Exporting SOLR Results'),
        'operations' => $operations,
        'init_message' => $this->t('Starting report generation...'),
        'progress_message' => $this->t('Estimated completion time: @estimate.'),
        'finished' => 'Drupal\solr_fusion_report\BatchService::exportSolrDataFinished',
      ];

      batch_set($batch);

      // Close the file handler since we don't need it anymore.
      fclose($output);
    }
  }

  /**
   * Get the form id.
   *
   * @return string
   *   The form id.
   */
  public function getFormId(): string {
    return 'solr_fusion_report_search_form';
  }

}
