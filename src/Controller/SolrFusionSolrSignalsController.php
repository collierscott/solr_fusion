<?php

namespace Drupal\solr_fusion\Controller;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Drupal\solr_fusion\Utils\ConfigurationBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Serializer;

/**
 * Controller to hand signal requests.
 */
class SolrFusionSolrSignalsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The client to send the request.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * A generator to generate UUID.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $generator;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Request stack that controls the lifecycle of requests.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   *   The request information.
   */
  private $request;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\solr_fusion\Controller\SolrFusionSolrSignalsController
   *   The controller
   */
  public static function create(ContainerInterface $container): SolrFusionSolrSignalsController {
    $request = \Drupal::request();
    $serializer = $container->get('serializer');
    $formats = $container->getParameter('serializer.formats');
    $generator = $container->get('uuid');
    $configFactory = $container->get('config.factory');
    $logger = $container->get('logger.factory');
    $client = $container->get('http_client');
    return new SolrFusionSolrSignalsController(
      $request,
      $serializer,
      $formats,
      $generator,
      $configFactory,
      $logger,
      $client
    );
  }

  /**
   * The constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Drupal\Component\Uuid\UuidInterface $generator
   *   A generator to generate a UUID.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A configuration factory to get config data.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger to log things.
   * @param \GuzzleHttp\Client $client
   *   The client to make requests.
   */
  public function __construct(
    Request $request,
    Serializer $serializer,
    array $serializer_formats,
    UuidInterface $generator,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $logger,
    Client $client,
  ) {
    $this->client = $client;
    $this->configFactory = $configFactory;
    $this->generator = $generator;
    $this->logger = $logger;
    $this->request = $request;
    $this->serializer = $serializer;
    $this->serializerFormats = $serializer_formats;
  }

  /**
   * Handle the request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function handleRequest(): JsonResponse {
    $format = $this->getRequestFormat($this->request);
    $requestContents = $this->request->getContent();
    $contents = $this->serializer->decode($requestContents, $format);

    $processedData = [];

    if (is_array($contents) && count($contents) > 0) {
      $bundle = $contents[0]['bundle'];
      $connector = $this->getConnector($bundle);

      if (!isset($connector)) {
        return new SolrFusionSolrErrorResponse(
          'Bad Request. No query connection configuration was found.',
          Response::HTTP_BAD_REQUEST
        );
      }

      $appId = $connector->get('app');
      foreach ($contents as $content) {
        $processedData[] = $this->processData($content, $appId);
      }
    }
    else {
      $bundle = $contents['bundle'];
      $connector = $this->getConnector($bundle);

      if (!isset($connector)) {
        return new SolrFusionSolrErrorResponse(
          'Bad Request. No query connection configuration was found.',
          Response::HTTP_BAD_REQUEST
        );
      }

      $appId = $connector->get('app');
      $processedData = $this->processData($contents, $appId);
    }

    $info = ConfigurationBuilder::getHostUsernamePassword($connector);

    $host = $info['host'];
    $username = $info['username'];
    $password = $info['password'];

    $scheme = $connector->get('scheme');
    $port = $connector->get('port');

    $path = $connector->get('signals_path');
    $app = $connector->get('app');

    $url = $scheme . '://' . $username . ':' . $password . '@';
    $url .= $host . ':' . $port . $path . '/' . $app . '?commit=true&async=true';

    try {
      $response = $this->client->request('POST', $url, ['body' => json_encode($processedData)]);
      $status = $response->getStatusCode();

      return new JsonResponse([
        'statusCode' => $status ,
        'statusMessage' => 'Success.',
      ], Response::HTTP_OK);
    }
    catch (GuzzleException $e) {
      $this->logger->get('solr_fusion')->error($e->getMessage());
      return new JsonResponse([
        'statusCode' => Response::HTTP_BAD_REQUEST,
        'statusMessage' => 'Bad Request. No response.',
      ], Response::HTTP_BAD_REQUEST);
    }

  }

  /**
   * Gets the format of the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The format of the request.
   */
  protected function getRequestFormat(Request $request): string {
    $format = $request->getRequestFormat();
    if (!in_array($format, $this->serializerFormats)) {
      throw new BadRequestHttpException("Unrecognized format: $format.");
    }
    return $format;
  }

  /**
   * Process the data.
   *
   * @param array $data
   *   The initial data.
   * @param string $appId
   *   The app id.
   *
   * @return array
   *   The updated data array.
   */
  protected function processData(array $data, string $appId): array {
    $data['id'] = $this->generator->generate();
    $data['params']['ip_address'] = $this->request->getClientIp();
    $data['params']['app_id'] = $appId;
    unset($data['bundle']);
    return $data;
  }

  /**
   * Get the correct connector.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The connector configuration.
   */
  protected function getConnector(string $bundle) {
    $queryConnector = $this->configFactory->get('solr_fusion.solr_query_connector.' . $bundle);
    $connectorId = $queryConnector->get('connector');

    if (!empty($connectorId)) {
      return $this->configFactory->get('solr_fusion.solr_connector.' . $connectorId);
    }

    return NULL;
  }

}
