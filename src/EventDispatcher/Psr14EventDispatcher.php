<?php

namespace Drupal\solr_fusion\EventDispatcher;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A dispatch wrapper.
 */
final class Psr14EventDispatcher extends ContainerAwareEventDispatcher implements EventDispatcherInterface {
  /**
   * The dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Construct the event dispatcher.
   */
  public function __construct() {
    $this->dispatcher = \Drupal::service('event_dispatcher');
    if (!$this->container) {
      $this->container = \Drupal::getContainer();
    }
    parent::__construct($this->container);
  }

  /**
   * Dispatch.
   *
   * @param object $event
   *   An event.
   * @param string|null $eventName
   *   An event name.
   *
   * @return object
   *   The event or proxy.
   */
  public function dispatch(object $event, ?string $eventName = NULL): object {
    /** @var \Symfony\Contracts\EventDispatcher\Event $event */
    return $this->dispatcher->dispatch(new EventProxy($event), \get_class($event));
  }

  /**
   * {@inheritDoc}
   */
  public function addListener($event_name, $listener, $priority = 0) {
    $this->dispatcher->addListener($event_name, $listener, $priority);
  }

}
