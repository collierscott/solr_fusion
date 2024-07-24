<?php

namespace Drupal\solr_fusion;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\solr_fusion\Exception\InvalidArgumentException;
use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A service for Solr searching.
 */
class SolrFusionSolrSearchService implements SolrFusionSolrSearchServiceInterface {

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
   * @param string $queryId
   *   The id of the solr query.
   *
   * @return \Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response or solr not found response.
   */
  public function search(string $queryId): JsonResponse|SolrFusionSolrErrorResponse {
    // This will get either a solr handler or a fusion handler.
    try {
      $handler = $this->solr->getHandlerToUse($queryId);
    }
    catch (InvalidArgumentException $e) {
      return new SolrFusionSolrErrorResponse(
        $e->getMessage(),
        Response::HTTP_BAD_REQUEST
      );
    }

    $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();

    $language = $this->request->get('language');
    $languages = array_keys($this->languageManager->getLanguages());

    if (!$language || !in_array($language, $languages)) {
      $language = $defaultLanguage;
    }

    if ($queryId === 'admin_search') {
      // Keys is needs to be q.
      $this->request->query->add([
        'q' => $this->request->query->get('keys'),
      ]);

      // All languages by default.
      $language = '*';

      if (
        $this->request->query->has('content_language') &&
        !empty($this->request->query->get('content_language'))
      ) {
        $contentLanguage = $this->request->query->get('content_language');

        if (in_array($contentLanguage, $languages)) {
          $language = $contentLanguage;
        }
      }
    }

    // Get a response.
    $response = $handler->getResponse($queryId, $this->request->query, $language);

    // Get the client request object.
    $clientRequest = $handler->getClientRequest();

    // If this happens, there's likely an issue with the connection details.
    if (is_null($response) || $response instanceof SolrFusionSolrErrorResponse) {
      return new JsonResponse([
        'statusCode' => Response::HTTP_BAD_REQUEST,
        'statusMessage' => 'Bad Request. No response - Check connection details.',
      ], Response::HTTP_BAD_REQUEST);
    }

    // When we successfully make a request to Solr/Fusion but get back an API
    // Error.
    if ($response->getStatusCode() != Response::HTTP_OK) {
      $code = $response->getStatusCode();
      $msg = $response->getStatusMessage();

      // Get the solr/fusion query that was run and log it.
      $query = $clientRequest->getUri();

      // Sometimes 'body' is a serialized string. Other times it's a json
      // string that contains the error (i.e. 'Unauthorized').
      $body = $response->getBody();

      $error_description = $this->getResponseErrorDescription($body);

      // Log the error to Drupal log.
      $log_message = sprintf('%s - "%s".%sQuery: %s', $msg, $error_description, PHP_EOL, $query);
      $this->logger->get('solr_fusion')->error($log_message);

      $settings = $this->solr->getSettings();
      $debug_mode = $settings->get('debug_mode');

      $error_response = [
        'statusCode' => $code,
        'statusMessage' => $msg,
        'statusDescription' => $error_description,
      ];

      if ($debug_mode) {
        $error_response['debugQuery'] = $query;
      }

      return new JsonResponse($error_response, $code);
    }

    return $handler->getJsonResponse($this->solr, $response, $language, $defaultLanguage);
  }

  /**
   * Get the error description from the response body.
   *
   * @param string $responseBody
   *   The response body string.
   *
   * @return string
   *   The error message.
   */
  private function getResponseErrorDescription(string $responseBody): string {
    $error_description = '';

    // Attempt to un-serialize the data.
    @$body_data = unserialize($responseBody, ['allowed_classes' => FALSE]);

    // Body data wasn't a serialized string.
    if (!$body_data) {
      // json_decode instead.
      $body_data = json_decode($responseBody);

      if (gettype($body_data) == 'object') {
        // If body_data is a class AND has `cause` -> `message`, get the data
        // so we can get the error message within.
        @$body_message = $body_data->cause->message;

        if (preg_match("/(.*}})\s/", (string) $body_message, $matches)) {
          $error_description = unserialize($matches[0], ['allowed_classes' => FALSE]);
          $error_description = $error_description['error']['msg'];
        }
        elseif (property_exists($body_data, 'code')) {
          $error_description = $body_data->code;
        }
      }
    }
    else {
      $error_description = $body_data['error']['msg'];
    }

    return $error_description;
  }

}
