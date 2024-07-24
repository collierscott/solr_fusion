<?php

namespace Drupal\solr_fusion\Fusion;

use Drupal\solr_fusion\Exception\UnexpectedValueException;

/**
 * Fusion endpoint.
 */
class FusionEndpoint extends Configurable {
  /**
   * Default options.
   *
   * The defaults match a standard Solr example instance as distributed by
   * the Apache Lucene Solr project.
   *
   * @var array
   */
  protected $options = [
    'scheme' => 'http',
    'host' => '127.0.0.1',
    'port' => NULL,
    'path' => '/',
    'collection' => NULL,
  ];

  /**
   * Magic method enables a object to be transformed to a string.
   *
   * @return string
   *   The uri in string readable form.
   */
  public function __toString() {
    return __CLASS__ . '::__toString' . "\n" . 'host: ' . $this->getHost() .
      "\n" . 'port: ' . $this->getPort() . "\n" . 'path: ' . $this->getPath() .
      "\n" . 'collection: ' . $this->getCollection() . "\n" .
      'authentication: ' . print_r($this->getAuthentication(), 1);
  }

  /**
   * Get key value.
   *
   * @return string|null
   *   The key value.
   */
  public function getKey(): ?string {
    return $this->getOption('key');
  }

  /**
   * Set key value.
   *
   * @param string $value
   *   The value based on a key.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setKey(string $value): self {
    $this->setOption('key_test', $value);

    return $this;
  }

  /**
   * Set host option.
   *
   * @param string $host
   *   This can be a hostname or an IP address.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setHost(string $host): self {
    $this->setOption('host', $host);

    return $this;
  }

  /**
   * Get host option.
   *
   * @return string|null
   *   Get the host name.
   */
  public function getHost(): ?string {
    return $this->getOption('host');
  }

  /**
   * Set port option.
   *
   * @param int $port
   *   Common values are 80, 8080 and 8983.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setPort(int $port): FusionEndpoint {
    $this->setOption('port', $port);

    return $this;
  }

  /**
   * Get port option.
   *
   * @return int|null
   *   Get the port number.
   */
  public function getPort(): ?int {
    return $this->getOption('port');
  }

  /**
   * Set path option.
   *
   * If the path has a trailing slash it will be removed.
   *
   * @param string $path
   *   Set the path option.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setPath(string $path): self {
    if ('/' === substr($path, -1)) {
      $path = substr($path, 0, -1);
    }

    $this->setOption('path', $path);

    return $this;
  }

  /**
   * Get path option.
   *
   * @return string|null
   *   The path.
   */
  public function getPath(): ?string {
    return $this->getOption('path');
  }

  /**
   * Set collection option.
   *
   * @param string $collection
   *   The collection to set.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setCollection(string $collection): self {
    $this->setOption('collection', $collection);

    return $this;
  }

  /**
   * Get collection option.
   *
   * @return string|null
   *   Return a collection.
   */
  public function getCollection(): ?string {
    return $this->getOption('collection');
  }

  /**
   * Set scheme option.
   *
   * @param string $scheme
   *   Set the scheme to use (http https).
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setScheme(string $scheme): self {
    $this->setOption('scheme', $scheme);

    return $this;
  }

  /**
   * Get scheme option.
   *
   * @return string|null
   *   The scheme to use (http https).
   */
  public function getScheme(): ?string {
    return $this->getOption('scheme');
  }

  /**
   * Set pipeline option.
   *
   * @param string $pipeline
   *   Set the pipeline to use.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setPipeline(string $pipeline): self {
    $this->setOption('pipeline', $pipeline);

    return $this;
  }

  /**
   * Get pipeline option.
   *
   * @return string|null
   *   The pipeline to use.
   */
  public function getPipeline(): ?string {
    return $this->getOption('pipeline');
  }

  /**
   * Set app option.
   *
   * @param string $app
   *   Set the app to use.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setApp(string $app): self {
    $this->setOption('app', $app);

    return $this;
  }

  /**
   * Get app option.
   *
   * @return string|null
   *   The app to use.
   */
  public function getApp(): ?string {
    return $this->getOption('app');
  }

  /**
   * Get the collection base url for all requests.
   *
   * Based on host, path, port and collection options.
   *
   * @return string
   *   The collection base uri.
   *
   * @throws \Drupal\solr_fusion\Exception\UnexpectedValueException
   *   An unexpected value exception.
   */
  public function getCollectionBaseUri(): string {
    $uri = '';

    $collection = $this->getCollection();

    if ($collection) {
      $uri .= '/collections/' . $collection;
    }
    else {
      throw new UnexpectedValueException('No collection set.');
    }

    return $uri;
  }

  /**
   * Get the pipeline base url for all requests.
   *
   * Based on host, path, port and collection options.
   *
   * @return string
   *   The collection base uri.
   *
   * @throws \Drupal\solr_fusion\Exception\UnexpectedValueException
   *   An unexpected value exception.
   */
  public function getPipelineBaseUrl(): string {
    $uri = '';
    $pipeline = $this->getPipeline();

    if ($pipeline) {
      $uri .= '/query-pipelines/' . $pipeline;
    }
    else {
      throw new UnexpectedValueException('No pipeline set.');
    }

    return $uri;
  }

  /**
   * Get the app base url for all requests.
   *
   * Based on host, path, port and collection options.
   *
   * @return string
   *   The collection base uri.
   *
   * @throws \Drupal\solr_fusion\Exception\UnexpectedValueException
   *   An unexpected value exception.
   */
  public function getAppBaseUrl(): string {
    $uri = '';
    $app = $this->getApp();

    if ($app) {
      $uri .= '/apps/' . $app;
    }
    else {
      throw new UnexpectedValueException('No apps set.');
    }

    return $uri;
  }

  /**
   * Get the base url for all V1 API requests.
   *
   * @return string
   *   The base uri.
   */
  public function getBaseUri(): string {
    try {
      $uri = $this->getServerUri();
      $uri .= $this->getAppBaseUrl();
      $uri .= $this->getPipelineBaseUrl();
      $uri .= $this->getCollectionBaseUri();
      return $uri;
    }
    catch (UnexpectedValueException $e) {
      throw new UnexpectedValueException('Neither app, pipeline, nor collection set.');
    }
  }

  /**
   * Get the server uri, required for non core/collection specific requests.
   *
   * @return string
   *   The server url.
   */
  public function getServerUri(): string {
    $auth = $this->getAuthentication();

    $uri = $this->getScheme() . '://';

    if (!empty($auth['username'] && !empty($auth['password']))) {
      $uri .= $auth['username'] . ':' . $auth['password'] . '@';
    }

    $uri .= $this->getHost();

    if (!empty($this->getPort())) {
      $uri .= ':' . $this->getPort();
    }

    $uri .= $this->getPath();
    return $uri;
  }

  /**
   * Set HTTP basic auth settings.
   *
   * If one or both values are NULL authentication will be disabled.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionEndpoint
   *   Provides fluent interface.
   */
  public function setAuthentication(string $username, string $password): self {
    $this->setOption('username', $username);
    $this->setOption('password', $password);

    return $this;
  }

  /**
   * Get HTTP basic auth settings.
   *
   * @return array
   *   An array with authentication values.
   */
  public function getAuthentication(): array {
    return [
      'username' => $this->getOption('username'),
      'password' => $this->getOption('password'),
    ];
  }

  /**
   * Initialization hook.
   *
   * In this case the path needs to be cleaned of trailing slashes.
   *
   * @see setPath()
   */
  protected function init() {
    foreach ($this->options as $name => $value) {
      switch ($name) {
        case 'path':
          $this->setPath($value);
          break;

        default:
          // Ignore.
      }
    }
  }

}
