<?php

namespace Drupal\solr_fusion_report;

use Drupal\solr_fusion\SolrFusionSolrSearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SolrFusion Solr Report Batch service class.
 *
 * @package Drupal\solr_fusion_report
 */
class BatchService {

  /**
   * Callback method to perform processing on each batch.
   *
   * Writes fetched data to an output file that will be
   * returned by callback_batch_finished().
   *
   * @param string $keys
   *   The search term used to fetch the results from SOLR.
   * @param string $page
   *   The page index when querying 100 results at a time.
   * @param string $filename
   *   The file to save the fetched results.
   * @param string $queryId
   *   The type of SOLR query to be performed.
   * @param string $totalResults
   *   The total number of results for the search term.
   * @param mixed $context
   *   Batch context information.
   */
  public static function exportSolrDataProcess($keys, $page, $filename, $queryId, $totalResults, &$context) {
    $languageManager = \Drupal::service('language_manager');
    $solr = \Drupal::service('solr_fusion.solr_service');
    $logger = \Drupal::service('logger.factory');

    $request = new Request();

    if ($keys) {
      // Re-open the created file to dump the CSV data in append mode.
      $output = fopen($filename, 'a');

      $request->query->add([
        'page' => $page,
      ]);

      $request->query->add([
        'keys' => $keys,
      ]);

      $searchService = new SolrFusionSolrSearchService($request, $solr, $languageManager, $logger);
      $response = $searchService->search($queryId);

      if ($response->getStatusCode() == Response::HTTP_OK) {
        $content = $response->getContent();

        // Decode into an associative array.
        $decoded = json_decode($content, TRUE);
        $documents = $decoded['body']['docs'];

        foreach ($documents as $document) {
          $row = self::buildRow($document);
          foreach ($row as $d) {
            // Add the data we exported to the next line of the CSV.
            fputcsv($output, $d);
          }
        }

        $context['results'] = [
          'filename' => $filename,
        ];

        $context['message'] = t('Exported @count of @total results.', [
          '@count' => ($page + 1) * 100,
          '@total' => $totalResults,
        ]);
      }
    }
  }

  /**
   * Callback method for batch finish.
   *
   * @param bool $success
   *   Indicates whether we hit a fatal PHP error.
   * @param array $results
   *   Contains batch results.
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function exportSolrDataFinished($success, $results, $operations) {
    if ($success) {
      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($results['filename']);
      $message = $message = t('Export complete. Download the file <a download href=":download_url">here</a>.', [':download_url' => $url]);
    }
    else {
      $message = t('Export failed. Check the error log for more details.');
    }

    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Helper function to extract data from a Solr result and convert to an array.
   *
   * @param array $document
   *   Solr result row to be processed.
   *
   * @return array
   *   Processed array containing Solr result.
   */
  public static function buildRow(array $document) {
    $data = [];

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

    $row[0] = $bundle;
    $row[1] = $languages;
    $row[2] = $title;
    $row[3] = $url;

    $data[] = $row;

    return $data;
  }

}
