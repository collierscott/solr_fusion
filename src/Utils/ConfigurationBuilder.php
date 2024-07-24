<?php

namespace Drupal\solr_fusion\Utils;

use Drupal\Core\Config\ImmutableConfig;

/**
 * A configuration builder.
 */
class ConfigurationBuilder {

  /**
   * Build an array of configurations.
   *
   * @param array $source
   *   The source to get configurations from.
   * @param string $index
   *   The index to use.
   *
   * @return array
   *   The array of configurations.
   */
  public static function buildConfigurations(array $source, string $index = 'id'): array {
    $result = [];
    foreach ($source as $name) {
      $config = \Drupal::config($name);
      $result[$config->get($index)] = $config;
    }
    return $result;
  }

  /**
   * Get the host, username, and password.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $connector
   *   Connector configuration.
   *
   * @return array
   *   An array of host, username, password.
   */
  public static function getHostUsernamePassword(ImmutableConfig $connector): array {
    $host = $connector->get('host');
    $username = $connector->get('username');
    $password = $connector->get('password');

    if (str_starts_with(trim($host), 'ENV:')) {
      $key = trim(str_replace('ENV:', '', $host));
      $host = getenv($key);
    }

    if (str_starts_with(trim($username), 'ENV:')) {
      $key = trim(str_replace('ENV:', '', $username));
      $username = getenv($key);
    }

    if (str_starts_with(trim($password), 'ENV:')) {
      $key = trim(str_replace('ENV:', '', $password));
      $password = getenv($key);
    }

    return [
      'host' => $host,
      'username' => $username,
      'password' => rawurlencode($password),
    ];
  }

}
