<?php

namespace Drupal\solr_fusion\Fusion;

/**
 * Configurable interface.
 */
interface ConfigurableInterface {

  /**
   * Set an option.
   *
   * @param string $name
   *   The option name.
   * @param mixed $value
   *   The value to use.
   *
   * @return \Drupal\solr_fusion\Fusion\ConfigurableInterface
   *   Provides fluent interface.
   */
  public function setOption(string $name, $value): ConfigurableInterface;

  /**
   * Set options.
   *
   * @param array $options
   *   An array of options.
   * @param bool $overwrite
   *   True for overwriting existing options, false for merging.
   *
   * @throws \Drupal\solr_fusion\Exception\InvalidArgumentException
   *   An invalid argument exception.
   *
   * @return \Drupal\solr_fusion\Fusion\ConfigurableInterface
   *   Return itself.
   */
  public function setOptions(array $options, bool $overwrite = FALSE): ConfigurableInterface;

  /**
   * Get an option value by name.
   *
   * If the option is empty or not set a NULL value will be returned.
   *
   * @param string $name
   *   The name of the option to get.
   *
   * @return mixed
   *   The return value.
   */
  public function getOption(string $name);

  /**
   * Get all options.
   *
   * @return array
   *   Get the options.
   */
  public function getOptions(): array;

}
