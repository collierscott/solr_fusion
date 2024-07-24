<?php

namespace Drupal\solr_fusion\Fusion;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A Fusion response.
 */
class FusionResponse {

  /**
   * Headers.
   *
   * @var array
   */
  protected $headers;

  /**
   * Body.
   *
   * @var string
   */
  protected $body;

  /**
   * HTTP response code.
   *
   * @var int
   */
  protected $statusCode;

  /**
   * HTTP response message.
   *
   * @var string
   */
  protected $statusMessage;

  /**
   * The fusion id.
   *
   * @var string
   */
  protected $fusionId;

  /**
   * Constructor.
   *
   * @param string $body
   *   The response body.
   * @param array $headers
   *   The headers.
   */
  public function __construct(string $body, array $headers = []) {
    $this->body = $body;
    if ($headers) {
      $this->setHeaders($headers);
    }
  }

  /**
   * Get body data.
   *
   * @return string
   *   The response body.
   */
  public function getBody(): string {
    return $this->body;
  }

  /**
   * Get response headers.
   *
   * @return array
   *   An array of headers.
   */
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * Get status code.
   *
   * @return int
   *   The status code thta was returned.
   */
  public function getStatusCode(): int {
    return $this->statusCode;
  }

  /**
   * Get status message.
   *
   * @return string
   *   The status message that was returned.
   */
  public function getStatusMessage(): string {
    return $this->statusMessage;
  }

  /**
   * Get the fusion id.
   *
   * @return string
   *   The fusion id.
   */
  public function getFusionId(): string {
    return $this->fusionId;
  }

  /**
   * Set headers.
   *
   * @param array $headers
   *   An array of headers.
   *
   * @return \Drupal\solr_fusion\Fusion\FusionResponse
   *   Provides fluent interface.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   A http exception.
   */
  public function setHeaders(array $headers): FusionResponse {
    $this->headers = $headers;

    // Get the status header.
    $statusHeader = NULL;
    $fusionId = NULL;
    foreach ($headers as $header) {
      if (0 === strpos($header, 'HTTP')) {
        $statusHeader = $header;
      }
      elseif (0 === strpos($header, 'x-fusion-query-id')) {
        $fusionId = $header;
      }
    }

    if (NULL === $statusHeader) {
      throw new HttpException('No HTTP status found');
    }

    $statusInfo = explode(' ', $statusHeader, 3);
    $this->statusCode = (int) $statusInfo[1];
    $this->statusMessage = $statusInfo[2];

    if (!empty($fusionId)) {
      $fusionInfo = explode(':', $fusionId);

      if (count($fusionInfo) > 1) {
        $this->fusionId = trim($fusionInfo[1]);
      }
    }

    return $this;
  }

}
