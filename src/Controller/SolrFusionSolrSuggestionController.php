<?php

namespace Drupal\solr_fusion\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\solr_fusion\Exception\InvalidArgumentException;
use Drupal\solr_fusion\Response\SolrFusionSolrErrorResponse;
use Drupal\solr_fusion\SolrFusionSolrServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to get the search suggestion keywords.
 */
class SolrFusionSolrSuggestionController extends ControllerBase {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Solr service.
   *
   * @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface
   */
  protected SolrFusionSolrServiceInterface $solr;


  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The language manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SolrFusionSolrServiceInterface $solr,
    LanguageManagerInterface $language_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->solr = $solr;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory'),
      $container->get('solr_fusion.solr_service'),
      $container->get('language_manager')
    );
  }

  /**
   * Search the suggester from solr and return the list of custom keyword.
   */
  public function search(Request $request): SolrFusionSolrErrorResponse|JsonResponse {
    $response = [];
    $key = $request->get('q');
    $query_id = 'suggest';

    if ($key) {
      // This will get either a solr handler or a fusion handler.
      try {
        $handler = $this->solr->getHandlerToUse($query_id);
      }
      catch (InvalidArgumentException $e) {
        return new SolrFusionSolrErrorResponse(
          $e->getMessage(),
          Response::HTTP_BAD_REQUEST
        );
      }
      $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();

      $language = $request->get('language');

      $languages = array_keys($this->languageManager()->getLanguages());
      if (!$language || !in_array($language, $languages)) {
        /** @var \Drupal\Core\Language\Language $language */
        $language = $defaultLanguage;
      }

      // Get a response.
      $response = $handler->getResponse($query_id, $request->query, $language->getId());

      if (is_null($response) || $response instanceof SolrFusionSolrErrorResponse) {
        return new JsonResponse([
          'statusCode' => Response::HTTP_BAD_REQUEST,
          'statusMessage' => 'Bad Request. No response - Check connection details.',
        ], Response::HTTP_BAD_REQUEST);
      }

      $jsonResponse = $handler->getJsonResponse($this->solr, $response, $language->getId(), $defaultLanguage);

      if (is_null($jsonResponse)) {
        return new SolrFusionSolrErrorResponse(
          'Bad Request. No json response.',
          Response::HTTP_BAD_REQUEST
        );
      }

      return $jsonResponse;
    }

    return new JsonResponse([]);
  }

}
