<?php

namespace Drupal\solr_fusion\Fusion;

/**
 * A class for Configurable classes.
 */
abstract class Configurable implements ConfigurableInterface {

  /**
   * Default options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructor.
   *
   * If options are passed they will be merged with {@link $options} using
   * the {@link setOptions()} method.
   *
   * After handling the options the {@link _init()} method is called.
   *
   * @param array|null $options
   *   An array of options.
   */
  public function __construct(array $options = NULL) {
    if ($options !== NULL) {
      $this->setOptions($options);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function setOptions(array $options, bool $overwrite = FALSE): ConfigurableInterface {
    if ($overwrite === TRUE) {
      $this->options = $options;
    }
    else {
      $this->options = array_merge($this->options, $options);
    }

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getOption(string $name) {
    return $this->options[$name] ?? NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getOptions(): array {
    return $this->options;
  }

  /**
   * {@inheritDoc}
   */
  public function setOption(string $name, $value): ConfigurableInterface {
    $this->options[$name] = $value;

    return $this;
  }

}
