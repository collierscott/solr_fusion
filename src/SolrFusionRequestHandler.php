<?php

namespace Drupal\solr_fusion;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Drupal\solr_fusion\Solr\SelectQuery;
use Solarium\Client;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Response as SolarResponse;
use Solarium\Core\Query\QueryInterface;
use Solarium\Exception\HttpException;
use Solarium\QueryType\Suggester\Query as SuggestQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * A handler for fusion requests.
 */
abstract class SolrFusionRequestHandler implements SolrFusionRequestHandlerInterface {

  use MessengerTrait;

  /**
   * An associative array of configurations.
   *
   * @var array
   */
  private array $configurations;

  /**
   * The query configuration.
   *
   * @var mixed
   */
  private mixed $queryConfiguration;

  /**
   * The logger channel interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * Get the Solarium client request.
   *
   * @var \Solarium\Core\Client\Request
   */
  protected Request $clientRequest;

  /**
   * Checks if the query is a suggestion.
   *
   * @var bool
   */
  protected bool $isSuggestion = FALSE;

  /**
   * The constructor.
   *
   * @param array $configurations
   *   The configurations.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel interface.
   */
  public function __construct(array $configurations, LoggerChannelFactoryInterface $logger) {
    $this->configurations = $configurations;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function getResponse(string $queryId, ParameterBag $parameterBag, string $language): Response|JsonResponse|SolrFusionSolrErrorResponse {
    $queryConnector = $this->configurations['query_connectors'][$queryId];

    if (!isset($queryConnector)) {
      return new SolrFusionSolrErrorResponse(
        'Bad Request. No query connection configuration was found.',
        Response::HTTP_BAD_REQUEST
      );
    }

    // From the query connector you can get the connector and the query to use.
    $queryToUse = $queryConnector->get('query');
    $connectorToUse = $queryConnector->get('connector');

    // Get the connector to use with this query.
    $connector = array_key_exists($connectorToUse, $this->configurations['connectors']) ?
      $this->configurations['connectors'][$connectorToUse] : $this->configurations['connectors']['solr'];

    if (!$connector) {
      return new SolrFusionSolrErrorResponse(
        'Bad Request. No connector configuration was found.',
        Response::HTTP_BAD_REQUEST
      );
    }

    $queryConfig = array_key_exists($queryToUse, $this->configurations['queries']) ?
      $this->configurations['queries'][$queryToUse] : FALSE;

    if (!$queryConfig) {
      return new SolrFusionSolrErrorResponse(
        'Bad Request. No query configuration was found.',
        Response::HTTP_BAD_REQUEST
      );
    }

    $this->queryConfiguration = $queryConfig;

    $client = $this->createClient($connector);

    if (is_null($client)) {
      $message = 'No configuration or connector was found for this path.';
      return new SolrFusionSolrErrorResponse(
        $message,
        Response::HTTP_BAD_REQUEST
      );
    }

    $q = $parameterBag->get('q');
    $q = strip_tags((string) $q);

    $fq = $parameterBag->get('fq');
    $fq = strip_tags((string) $fq);

    if (empty(strpos($q, '%'))) {
      $q = urldecode($q);
    }
    if (empty($q)) {
      // Don't use the standard query parser(q.alt), when no q param is sent to
      // ensure the boost fields work consistently on sorting by weight. This is
      // done by just providing a `q` value.
      $q = '*';
    }

    $bundle = !empty($queryConfig->get('bundle')) ? $queryConfig->get('bundle') : $queryId;

    if ($queryId === 'suggest') {
      $this->isSuggestion = TRUE;
      $query = new SuggestQuery(['responsewriter' => 'phps']);
      $query->setQuery($q);
      return $this->getSuggestSearchResponse($client, $query);
    }
    else {
      if ($bundle == 'search' && empty($q)) {
        return new SolrFusionSolrErrorResponse(
          'Bad Request. No query parameter was found for search.',
          Response::HTTP_BAD_REQUEST
        );
      }

      $filters = $parameterBag->all('f') ?: [];

      if (empty(array_filter($filters))) {
        $filters = [];
      }

      // Loop through filters & add quotes to the facet values. This addresses
      // an issue where the facet value contains spaces.
      if (!empty($filters)) {
        foreach ($filters as $index => $filter) {
          $tokens = explode(':', $filter);
          $filters[$index] = "$tokens[0]:\"$tokens[1]\"";
        }
      }

      $start = !empty($parameterBag->get('page')) && is_numeric($parameterBag->get('page')) ? abs($parameterBag->get('page')) : 0;
      $dateSort = $parameterBag->get('date_sort');

      $query = $this->buildSelectQuery($queryId, $queryConfig, $bundle, $start, $dateSort, $language, $filters, 'phps');

      if ($queryId == 'blog_post_channel' && !empty($fq)) {
        $query->createFilterQuery('taxonomy_blog_post_category')->setQuery($fq);
      }

      if ($queryId == 'blog_author_list' && !empty($fq)) {
        $query->createFilterQuery('post_authors')->setQuery($fq);
      }

      if ($queryId != 'admin_search') {
        $this->buildQalt($query);
      }
      else {
        $filterQueries = $query->getFilterQueries();

        foreach ($filterQueries as $filterQuery) {
          $filterQuery->addTag($filterQuery->getKey());
        }
      }
    }

    return $this->getSearchResponse($client, $query, $queryConfig, $q, $queryId);
  }

  /**
   * Get a search response.
   *
   * @param \Solarium\Client $client
   *   The client to use.
   * @param \Drupal\solr_fusion\Solr\SelectQuery $query
   *   The $query.
   * @param \Drupal\Core\Config\ImmutableConfig $queryConfiguration
   *   The query configuration.
   * @param string|null $q
   *   The q (query)
   * @param string|null $queryId
   *   The query Id.
   *
   * @return \Solarium\Core\Client\Response|null
   *   The response.
   */
  protected function getSearchResponse(
    Client $client,
    SelectQuery $query,
    ImmutableConfig $queryConfiguration,
    string $q = NULL,
    string $queryId = NULL,
  ): ?SolarResponse {
    // Manually create a request for the query.
    $solrRequest = $client->createRequest($query);
    $this->clientRequest = $solrRequest;

    // Clean up the query.
    $solrRequest->removeParam('fl');
    $solrRequest->removeParam('q');
    $solrRequest->removeParam('json.nl');
    $solrRequest->addParam('json.nl', 'map');
    $solrRequest->addParam('fl', $query->getDefaultFl());

    $fieldListItems = $queryConfiguration->get('field_list');

    $items = explode('|', $fieldListItems);

    foreach ($items as $item) {
      $solrRequest->addParam('fl', $item);
    }

    if ($q) {
      $solrRequest->addParam('q', $q);
    }

    if ($queryId == 'admin_search') {
      $allParams = $solrRequest->getParams();

      if (array_key_exists('facet.field', $allParams)) {
        foreach ($allParams['facet.field'] as $index => $facet) {
          $allParams['facet.field'][$index] = str_replace('!key=', '!ex=', $facet);
        }
        $solrRequest->addParam('facet.field', $allParams['facet.field'], TRUE);
      }
    }

    try {
      return $client->executeRequest($solrRequest);
    }
    catch (HttpException $ex) {
      $message = 'A 400 error occurred while trying to contact Solr.';
      $this->logger->get('solr_fusion')->error($message);
      return NULL;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getJsonResponse(SolrFusionSolrServiceInterface $service, $response, string $language, string $defaultLanguage): JsonResponse|SolrFusionSolrErrorResponse {
    // Do something with the response.
    /** @var \Solarium\Core\Client\Response $response */
    if ($response->getStatusCode() == Response::HTTP_OK) {
      $headers = $response->getHeaders();
      $statusMessage = $response->getStatusMessage();
      $statusCode = $response->getStatusCode();
      $body = unserialize($response->getBody(), ['allowed_classes' => FALSE]);
      $settings = $service->getSettings();
      $debug_mode = $settings->get('debug_mode');

      if (!is_bool($body)) {
        if ($this->isSuggestion) {
          $suggestTerms = $this->getSuggestTerms($body);
          $suggestJsonResponse = [
            'statusCode' => $statusCode,
            'statusMessage' => $statusMessage,
            'responseHeader' => [],
            'headers' => $headers,
            'body' => $suggestTerms,
          ];

          if ($debug_mode) {
            $suggestJsonResponse['debugQuery'] = $this->clientRequest->getUri();
          }

          return new JsonResponse($suggestJsonResponse);
        }

        // Only include facets configured for the query.
        $facetCounts = $body['facet_counts'];
        $facetList = $this->queryConfiguration->get('facet_list');
        if (!empty($facetList)) {
          $list = explode('|', $facetList);
          $keepers = [];

          foreach ($facetCounts['facet_fields'] as $key => $field) {
            if (in_array($key, $list)) {
              $keepers[$key] = $field;
            }
          }

          $facetCounts['facet_fields'] = $keepers;
        }

        $translatedTerms = $service->getTranslations($facetCounts, $language, $defaultLanguage);

        $responseHeader = array_key_exists('responseHeader', $body) ? $body['responseHeader'] : [];
        $bodyResponse = $body['response'];

        if (array_key_exists('docs', $bodyResponse)) {
          $bodyResponse['docs'] = $this->processBodyResponseDocs($bodyResponse['docs'], $language);
        }

        $jsonResponse = [
          'statusCode' => $statusCode,
          'statusMessage' => $statusMessage,
          'responseHeader' => $responseHeader,
          'headers' => $headers,
          'body' => $bodyResponse,
          'facetCounts' => $facetCounts,
          'facetTranslations' => $translatedTerms,
          'highlighting' => array_key_exists('highlighting', $body) ? $body['highlighting'] : '',
        ];

        if ($debug_mode) {
          $jsonResponse['debugQuery'] = $this->clientRequest->getUri();
        }

        if ($this->getRequestHandlerType() == 'fusion') {
          $jsonResponse['fusionQueryId'] = $this->getFusionId($headers);
        }

        return new JsonResponse($jsonResponse);
      }
      else {
        $message = 'An error occurred while deserializing the response body.';
        return new SolrFusionSolrErrorResponse($message, Response::HTTP_PRECONDITION_FAILED);
      }
    }
    else {
      $message = 'An error occurred while trying to contact Solr.';
      return new SolrFusionSolrErrorResponse($message, $response->getStatusCode());
    }
  }

  /**
   * Convert the docs to fit what the client expects.
   *
   * @param array $docs
   *   An array of docs.
   * @param string $language
   *   The language.
   *
   * @return array
   *   Updated docs.
   */
  private function processBodyResponseDocs(array $docs, string $language): array {
    $newDocs = [];

    foreach ($docs as $doc) {
      $updated = $doc;

      // Overrides.
      $updated['url'] = $doc['url'] ?? '';
      $updated['elevated'] = FALSE;

      if (array_key_exists('Content_Language', $doc)) {
        $updated['content_language'] = $doc['Content_Language'][0];
      }
      elseif (array_key_exists('content_language', $doc)) {
        $updated['content_language'] = $doc['content_language'][0];
      }
      else {
        $updated['content_language'] = [$language];
      }

      $newDocs[] = $updated;
    }

    return $newDocs;
  }

  /**
   * Create the client.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $connector
   *   The query id to use to get the client.
   *
   * @return \Solarium\Client|null
   *   The client.
   */
  abstract protected function createClient(ImmutableConfig $connector): ?Client;

  /**
   * Get a search response.
   *
   * @param \Solarium\Client $client
   *   The client to use.
   * @param \Solarium\Core\Query\QueryInterface $query
   *   The $query.
   *
   * @return \Solarium\Core\Client\Response|null
   *   The response.
   */
  protected function getSuggestSearchResponse(Client $client, QueryInterface $query): ?SolarResponse {
    // Manually create a request for the query.
    $solrRequest = $client->createRequest($query);
    $this->clientRequest = $solrRequest;

    try {
      return $client->executeRequest($solrRequest);
    }
    catch (HttpException $ex) {
      $message = 'A 400 error occurred while trying to contact Solr.';
      $this->logger->get('solr_fusion')->error($message);
      return NULL;
    }
  }

  /**
   * Build the select query.
   *
   * @param string $solrQuery
   *   The solr query.
   * @param \Drupal\Core\Config\ImmutableConfig $queryConfiguration
   *   Query configuration information.
   * @param string|null $bundle
   *   The bundle to use.
   * @param int $start
   *   The starting point.
   * @param string|null $dateSort
   *   The data sort.
   * @param string $language
   *   The language.
   * @param array|null $filters
   *   The filters.
   * @param string|null $responseWriter
   *   The response writer.
   *
   * @return \Drupal\solr_fusion\Solr\SelectQuery
   *   The select query to use.
   */
  protected function buildSelectQuery(
    string $solrQuery,
    ImmutableConfig $queryConfiguration,
    string $bundle = NULL,
    int $start = 0,
    string $dateSort = NULL,
    string $language = 'en',
    array $filters = NULL,
    string $responseWriter = NULL,
  ): SelectQuery {
    $settings = $this->configurations['settings'];

    $options = [];

    if (!is_null($responseWriter)) {
      $options['responsewriter'] = $responseWriter;
    }

    $query = new SelectQuery($options);
    $query->setOmitHeader($settings->get('omit_header') == 1);
    $query->defaultParameters(TRUE);
    $query->addParam('facet.mincount', 1);

    $parameters = $this->configurations['parameters'];

    // Add global parameters.
    foreach ($parameters as $parameter) {
      $query->addParam($parameter->get('label'), $parameter->get('value'));
    }

    $query->removeParam('start');
    $query->removeParam('rows');

    $rowCount = $solrQuery == 'admin_search' ? 100 : self::NUM_OF_ROWS;

    $offset = $start * $rowCount;
    $query->setStart($offset)->setRows($rowCount);

    // Handle boost filters (bf).
    if (trim($queryConfiguration->get('boost_field'))) {
      $query->addParam('bf', $queryConfiguration->get('boost_field'));
    }

    // Add any sorting configured for the solr query.
    if ($solrQuery == 'event' && $dateSort == 'previous') {
      $sorts = ['start_date:desc'];
    }
    else {
      $sorts = explode('|', $queryConfiguration->get('sort'));
    }

    if (!empty($sorts)) {
      foreach ($sorts as $sort) {
        $tokens = explode(':', $sort);

        if (count($tokens) > 1) {
          $query->addSort($tokens[0], $tokens[1]);
        }
      }
    }

    if ($solrQuery == 'blog_post' || $solrQuery == 'blog_channel') {
      $query->addParam('q.op', 'AND');
    }

    // Handle multiple of same key.
    if (is_array($filters)) {
      // Check if we can substitute out the filters if fields are renamed.
      $renamed_facets = $queryConfiguration->get('renamed_facet_field');
      if ($renamed_facets) {
        $renamed_facets = explode('|', $renamed_facets);
        $filters = $this->renameFacetString($filters, $renamed_facets);
      }

      $useOrJoin = FALSE;

      if ($solrQuery == 'admin_search') {
        $useOrJoin = TRUE;
      }

      $query->addFilters($filters, $useOrJoin);
    }

    if ($solrQuery !== 'search' && $solrQuery !== 'admin_search') {
      $query->createFilterQuery('bundle')->setQuery('bundle:' . $bundle);
    }

    if ($solrQuery == 'event' && $dateSort == 'previous') {
      $query->createFilterQuery('event_sort')->setQuery('end_date:[* TO NOW]');
    }
    else {
      // Filter Queries (fq).
      $filterQueries = explode('|', $queryConfiguration->get('filter_query_list'));

      if (!empty($filterQueries)) {
        $index = 0;
        foreach ($filterQueries as $filter) {
          if (!empty($filter)) {
            $query->createFilterQuery('filters_' . $index++)->setQuery($filter);
          }
        }
      }
    }

    // Query Fields (qf).
    $queryFields = explode('|', $queryConfiguration->get('query_field_list'));
    $qf = $query->buildQf($queryFields, $language);

    if (count($qf) > 0) {
      $query->addParam('qf', $qf);
    }

    if ($solrQuery !== 'admin_search') {
      $query->createFilterQuery('content_language')
        ->setQuery('content_language:(' . $language . ')');
      // ITMKTGCMS-8291 - Only show language translations.
      $query->createFilterQuery('Content_Language')
        ->setQuery('Content_Language:(' . $language . ')');
    }
    elseif ($language == '*') {
      $language = 'content_language';
    }

    $query->addFacets($language, $queryConfiguration->get('facet_list'), $this->configurations['facets']);

    return $query;
  }

  /**
   * Get the client request object.
   *
   * @return \Solarium\Core\Client\Request
   *   Solarium Client Request object.
   */
  public function getClientRequest(): Request {
    return $this->clientRequest;
  }

  /**
   * Build the Qalt for the query.
   *
   * @param \Drupal\solr_fusion\Solr\SelectQuery $query
   *   The select query.
   */
  protected function buildQalt(SelectQuery $query) {
    $qAlt = [];

    foreach ($query->getFilterQueries() as $fq) {
      $qAlt[] = '(' . $fq->getOptions()['query'] . ')';
    }

    if ($qAlt) {
      $query->addParam('q.alt', implode(' AND ', $qAlt));
    }
  }

  /**
   * Get the Fusion Query ID from the response headers.
   *
   * @return string
   *   Fusion Query ID.
   */
  protected function getFusionId(array $headers): string {
    foreach ($headers as $header) {
      if (str_starts_with($header, 'x-fusion-query-id')) {
        $fusionId = $header;
        break;
      }
    }

    if (!empty($fusionId)) {
      $fusionInfo = explode(':', $fusionId);

      if (count($fusionInfo) > 1) {
        return trim($fusionInfo[1]);
      }
    }

    return '';
  }

  /**
   * Get the type of the request handler.
   *
   * @return string
   *   The client.
   */
  abstract protected function getRequestHandlerType(): string;

  /**
   * Rename the facets string from old to new.
   *
   * @param array $filters
   *   The facet filters.
   * @param array $renamed_facets
   *   The old to new facet field ie old:new.
   *
   * @return array
   *   The new facet query params.
   */
  protected function renameFacetString(array $filters, array $renamed_facets): array {
    if ($filters && $renamed_facets) {
      foreach ($filters as &$filter) {
        $facet_key = explode(':', $filter);
        foreach ($renamed_facets as $facet) {
          [$from, $to] = explode(':', $facet);
          if ($from && $to && $facet_key[0] === $from) {
            $filter = preg_replace('/^' . $from . ':/', "$to:", $filter);
          }
        }
      }
    }
    return $filters;
  }

  /**
   * Get the terms from the body response via the suggest query.
   *
   * @param array $body
   *   The response from the body.
   *
   * @return array
   *   Return the terms for each suggestion.
   */
  public function getSuggestTerms(array $body): array {
    $terms = [];
    if (!empty($body['suggest'])) {
      foreach ($body['suggest'] as $suggest) {
        foreach ($suggest as $search_term) {
          if ($search_term['suggestions']) {
            foreach ($search_term['suggestions'] as $suggestions) {
              $terms[] = $suggestions['term'];
            }
          }
        }
      }
    }
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshIndex($queryId, array $urls):bool {
    $queryConnector = $this->configurations['query_connectors'][$queryId];

    if (!isset($queryConnector)) {
      $this->logMessage('missing_query_config');
      return FALSE;
    }

    // From the query connector you can get the connector and the query to use.
    $connectorToUse = $queryConnector->get('connector');

    // Get the connector to use with this query.
    $connector = array_key_exists($connectorToUse, $this->configurations['connectors']) ?
      $this->configurations['connectors'][$connectorToUse] : $this->configurations['connectors']['solr'];

    if (!$connector) {
      $this->logMessage('missing_connector');
      return FALSE;
    }

    $datasourceName = $this->configurations['recrawl_settings']->get('datasource_name');
    $datasource_config = $this->getDatasourceConfig($connector, $datasourceName);
    if (!empty($datasource_config['properties'])) {
      // Get full urls passed in.
      $datasource_config['properties']['startLinks'] = $this->getFullUrls($urls);
      $datasource_config['properties']['maxItem'] = count($urls);
      $response_code = $this->updateDataSourceConfig($connector, $datasourceName, $datasource_config);
      if ($response_code === 200) {
        // Run the Schedule job.
        $job = $this->runDatasourceJob($connector, $datasourceName);
        if (!empty($job['accepted'])) {
          $this->messenger()->addStatus('Content has been marked for update in
            Solr/Lucidworks. Url: ' . implode(',', $urls));
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Update the datasource config.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $connector
   *   The query configuration.
   * @param string $datasourceName
   *   The datasource name.
   * @param array $config
   *   The full configuration data that will be updated to.
   *
   * @return int
   *   Return the response code.
   */
  public function updateDataSourceConfig($connector, $datasourceName, array $config):int {
    $client = $this->createClient($connector, [
      'path' => 'connectors/datasources/' . $datasourceName,
    ]);

    if (is_null($client)) {
      $this->logMessage('missing_connector');
      return Response::HTTP_BAD_REQUEST;
    }
    $endpoint = $client->getEndpoint();
    $path = $endpoint->getOption('scheme') . '://' . $endpoint->getOption('host') . $endpoint->getOption('path');
    $results = \Drupal::httpClient()->put($path, [
      'verify' => TRUE,
      'http_errors' => FALSE,
      // Pass in the array config that will be encoded later by httpClient.
      'json' => $config,
    ]);

    return $results->getStatusCode();
  }

  /**
   * Get the datasource by the name.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $connector
   *   The query configuration.
   * @param string $datasourceName
   *   The datasource name.
   *
   * @return array
   *   Return the response of the job once its trigger to start. If connection
   *   failed return the json response.
   */
  public function runDatasourceJob($connector, $datasourceName): array {
    $client = $this->createClient($connector, ['path' => 'jobs/datasource:' . $datasourceName . '/actions']);

    if (is_null($client)) {
      $this->logMessage('missing_connector');
      return [];
    }
    $endpoint = $client->getEndpoint();
    $path = $endpoint->getOption('scheme') . '://' . $endpoint->getOption('host') . $endpoint->getOption('path');
    $results = \Drupal::httpClient()->post($path, [
      'verify' => FALSE,
      'json' => [
        'action' => 'start',
      ],
    ]);

    if ($results->getStatusCode() !== 200) {
      $message = 'Status code: @statusCode: Failed to get a response from the server.';
      $this->logMessage('custom', $message, $results->getStatusCode());
      return [];
    }

    return json_decode($results->getBody()->getContents(), TRUE);
  }

  /**
   * Get the datasource configuration by connection details and datasource name.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $connector
   *   The query configuration.
   * @param string $datasourceName
   *   The datasource name.
   *
   * @return array
   *   The configuration of the datasource in an array.
   */
  public function getDatasourceConfig(ImmutableConfig $connector, string $datasourceName):array {
    $client = $this->createClient($connector, ['path' => 'connectors/datasources/' . $datasourceName]);

    if (is_null($client)) {
      $this->logMessage('missing_config');
      return [];
    }
    $endpoint = $client->getEndpoint();
    $path = $endpoint->getOption('scheme') . '://' . $endpoint->getOption('host') . $endpoint->getOption('path');
    $results = \Drupal::httpClient()->get($path, [
      'verify' => TRUE,
      'http_errors' => FALSE,
    ]);

    if ($results->getStatusCode() !== 200) {
      $message = 'Failed to get a response from the server.';
      $this->logMessage('custom', $message, $results->getStatusCode());
      return [];
    }
    $content = $results->getBody()->getContents();
    if (!is_null($content)) {
      return json_decode($content, TRUE);
    }
    return [];
  }

  /**
   * Append the domain use for the startLink.
   *
   * The accessible domain that LW can reach during the crawl.
   *
   * @param array $urls
   *   An array of urls with relative path with a leading slash,
   *   /path/to/content.
   */
  public function getFullUrls(array $urls) {
    $domain = $this->configurations['recrawl_settings']->get('domain');
    foreach ($urls as $delta => $url) {
      $urls[$delta] = $domain . $url;
    }
    return $urls;
  }

  /**
   * Log messages to solr_fusion if there is an error.
   *
   * @param string{'missing_connector'|'missing_query_config'|'custom'} $type
   *   The error type, use "custom" to provide own message and status code.
   * @param string $message
   *   Optional message that should contain the "@statusCode" in string when
   *   using the type "custom".
   * @param int $statusCode
   *   Optional status code when using the type 'custom'.
   */
  protected function logMessage($type, $message = '', $statusCode = 0): void {
    switch ($type) {
      case 'missing_connector':
        $this->logger->get('solr_fusion')->error('Status code: @statusCode:
          No configuration or connector was found for this path.', [
            '@statusCode:' => Response::HTTP_BAD_REQUEST,
          ]
        );
        break;

      case 'missing_query_config':
        $this->logger->get('solr_fusion')->error('Status code: @statusCode: Bad
          Request. No query connection configuration was found.', [
            '@statusCode:' => Response::HTTP_BAD_REQUEST,
          ]
        );

        break;

      case 'custom':
        $this->logger->get('solr_fusion')->error($message, [
          '@statusCode' => $statusCode,
        ]);
        break;

      default:
        break;
    }
  }

  /**
   * Delete document from solr index.
   *
   * @param string $queryId
   *   Query id used for the connection.
   * @param string $document_path
   *   The document path / URL which is to be deleted.
   *
   * @return array
   *   Return the "status_code" key and code in the array.
   */
  public function deleteDocument($queryId, $document_path) :array {
    $output = [];
    $queryConnector = $this->configurations['query_connectors'][$queryId];
    // From the query connector you can get the connector and the query to use.
    $connectorToUse = $queryConnector->get('connector');

    // Get the connector to use with this query.
    $connector = array_key_exists($connectorToUse, $this->configurations['connectors']) ?
          $this->configurations['connectors'][$connectorToUse] : $this->configurations['connectors']['solr'];

    if (!$connector) {
      $this->logMessage('missing_connector');
      return $output;
    }

    $client = $this->createClient($connector, ['path' => 'index-pipelines/solr-fusioncms/collections/solr-fusioncms/index']);

    if (is_null($client)) {
      $this->logMessage('missing_connector');
      return $output;
    }
    $endpoint = $client->getEndpoint();
    $path = $endpoint->getOption('scheme') . '://' . $endpoint->getOption('host') . $endpoint->getOption('path');
    $path = str_replace('/apps/' . $connector->get('app'), '', $path);
    $commands = [
      [
        'name' => 'delete',
        'params' => [],
      ],
      [
        'name' => 'commit',
        'params' => [],
      ],
    ];
    $results = \Drupal::httpClient()->post($path, [
      'verify' => FALSE,
      'json' => [
        [
          'id' => $document_path,
        ],
        [
          'commands' => $commands,
        ],
      ],
    ]);
    $status_code = $results->getStatusCode();
    if ($status_code !== 204) {
      $message = 'Status code: @statusCode: Failed to get a response from the server.';
      $this->logMessage('custom', $message, $status_code);
    }
    $output['status_code'] = $status_code;
    return $output;
  }

}
