<?php

namespace Drupal\solr_fusion\Fusion;

/**
 * Fusion Request.
 */
class FusionRequest extends Configurable {

  /**
   * Request GET method.
   */
  const METHOD_GET = 'GET';

  /**
   * Request POST method.
   */
  const METHOD_POST = 'POST';

  /**
   * V1 API.
   */
  const API_V1 = 'v1';

  /**
   * Request params.
   *
   * @var array
   */
  protected $params = [];

  /**
   * Default options.
   *
   * @var array
   */
  protected $options = [
    'method' => self::METHOD_GET,
    'api' => self::API_V1,
  ];

  /**
   * Request headers.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * Raw POST data.
   *
   * @var string
   */
  protected $rawData;

  /**
   * Magic method enables a object to be transformed to a string.
   *
   * @return string
   *   The string of the object.
   */
  public function __toString() {
    return __CLASS__ . '::__toString' . "\n" . 'method: ' .
      $this->getMethod() . "\n" . 'header: ' .
      print_r($this->getHeaders(), 1) . 'authentication: ' .
      print_r($this->getAuthentication(), 1) . 'resource: ' .
      $this->getUri() . "\n" . 'resource urldecoded: ' .
      urldecode($this->getUri()) . "\n" . 'raw data: ' .
      $this->getRawData() . "\n";
  }

  /**
   * Set request handler.
   *
   * @param string|null $handler
   *   The request handler.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function setHandler(?string $handler): self {
    $this->setOption('handler', $handler);

    return $this;
  }

  /**
   * Get request handler.
   *
   * @return string|null
   *   The request handler.
   */
  public function getHandler(): ?string {
    return $this->getOption('handler');
  }

  /**
   * Set request method.
   *
   * Use one of the constants as value.
   *
   * @param string $method
   *   The method to use for the request.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function setMethod(string $method): self {
    $this->setOption('method', $method);

    return $this;
  }

  /**
   * Get request method.
   *
   * @return string|null
   *   The method if any is set.
   */
  public function getMethod(): ?string {
    return $this->getOption('method');
  }

  /**
   * Get raw POST data.
   *
   * @return string|null
   *   The raw data if any.
   */
  public function getRawData(): ?string {
    return $this->rawData;
  }

  /**
   * Set raw POST data.
   *
   * This string must be safely encoded.
   *
   * @param string $data
   *   The data for the post.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function setRawData(string $data): self {
    $this->rawData = $data;

    return $this;
  }

  /**
   * Get all request headers.
   *
   * @return array
   *   An array of header values.
   */
  public function getHeaders(): array {
    return array_unique($this->headers);
  }

  /**
   * Get the header value.
   *
   * @param string $headerName
   *   The header value.
   *
   * @return string|null
   *   The header value.
   */
  public function getHeader(string $headerName): ?string {
    foreach ($this->headers as $header) {
      [$name] = explode(':', $header);

      if ($name === $headerName) {
        return $header;
      }
    }

    return NULL;
  }

  /**
   * Set request headers.
   *
   * @param array $headers
   *   Set headers with an array.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function setHeaders(array $headers): self {
    $this->clearHeaders();
    $this->addHeaders($headers);

    return $this;
  }

  /**
   * Add a request header.
   *
   * @param string|array $value
   *   The header to add.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function addHeader($value): self {
    $this->headers[] = $value;

    return $this;
  }

  /**
   * Add multiple headers to the request.
   *
   * @param array $headers
   *   Headers to add.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function addHeaders(array $headers): self {
    foreach ($headers as $header) {
      $this->addHeader($header);
    }

    return $this;
  }

  /**
   * Clear all request headers.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function clearHeaders(): self {
    $this->headers = [];

    return $this;
  }

  /**
   * Get an URI for this request.
   *
   * @return string|null
   *   The uri for the request as a string.
   */
  public function getUri(): ?string {
    return $this->getHandler() . '?' . $this->getQueryString();
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
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
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
   *   The authectication array.
   */
  public function getAuthentication(): array {
    return [
      'username' => $this->getOption('username'),
      'password' => $this->getOption('password'),
    ];
  }

  /**
   * Initialization hook.
   */
  protected function init() {
    foreach ($this->options as $name => $value) {
      switch ($name) {
        case 'rawdata':
          $this->setRawData($value);
          break;

        case 'param':
          $this->setParams($value);
          break;

        case 'header':
          $this->setHeaders($value);
          break;

        case 'authentication':
          if (isset($value['username'], $value['password'])) {
            $this->setAuthentication($value['username'], $value['password']);
          }
          break;

        default:
          // Ignore.
      }
    }
  }

  /**
   * Get a param value.
   *
   * @param string $key
   *   The name of the parameter.
   *
   * @return string|array
   *   The parameters.
   */
  public function getParam(string $key) {
    if (isset($this->params[$key])) {
      return $this->params[$key];
    }
    return '';
  }

  /**
   * Get all params.
   *
   * @return array
   *   An array of parameters.
   */
  public function getParams(): array {
    return $this->params;
  }

  /**
   * Set request params.
   *
   * @param array $params
   *   The request parameters.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interfacee
   */
  public function setParams(array $params): FusionRequest {
    $this->clearParams();
    $this->addParams($params);

    return $this;
  }

  /**
   * Add a request param.
   *
   * If you add a request param that already exists the param will be converted
   *   into a multi-value param, unless you set the over write param to true.
   *
   * Empty params are not added to the request.
   *
   * @param string $key
   *   The parameter key.
   * @param string|array $value
   *   The value of the parameter.
   * @param bool $overwrite
   *   Should values be overwritten.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function addParam(string $key, $value, bool $overwrite = FALSE): FusionRequest {
    if ($value !== NULL && $value !== []) {
      if (!$overwrite && isset($this->params[$key])) {
        if (!is_array($this->params[$key])) {
          $this->params[$key] = [$this->params[$key]];
        }
        $this->params[$key][] = $value;
      }
      else {
        if ($value === TRUE) {
          $value = 'true';
        }
        elseif ($value === FALSE) {
          $value = 'false';
        }

        $this->params[$key] = $value;
      }
    }

    return $this;
  }

  /**
   * Add multiple params to the request.
   *
   * @param array $params
   *   An array of parameters.
   * @param bool $overwrite
   *   Should values be overwritten.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function addParams(array $params, bool $overwrite = FALSE): FusionRequest {
    foreach ($params as $key => $value) {
      $this->addParam($key, $value, $overwrite);
    }

    return $this;
  }

  /**
   * Remove a param by key.
   *
   * @param string $key
   *   The key of the parameter.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function removeParam(string $key): FusionRequest {
    if (isset($this->params[$key])) {
      unset($this->params[$key]);
    }

    return $this;
  }

  /**
   * Clear all request params.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionRequest
   *   Provides fluent interface
   */
  public function clearParams(): FusionRequest {
    $this->params = [];

    return $this;
  }

  /**
   * Get the query string for this request.
   *
   * @param string $separator
   *   The query string separator.
   *
   * @return string
   *   The query string that is used.
   */
  public function getQueryString(string $separator = '&'): string {
    $queryString = '';
    if (count($this->params) > 0) {
      $queryString = http_build_query($this->params, '', $separator);
      $queryString = preg_replace(
        '/%5B(?:\d|[1-9]\d+)%5D=/',
        '=',
        $queryString
      );
    }

    return $queryString;
  }

}
