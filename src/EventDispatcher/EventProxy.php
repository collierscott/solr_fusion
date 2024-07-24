<?php

namespace Drupal\solr_fusion\EventDispatcher;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * A proxy for events defined by symfony contracts.
 */
class EventProxy extends Event {
  /**
   * The event.
   *
   * @var \Symfony\Contracts\EventDispatcher\Event
   */
  protected $event;

  /**
   * EventProxy constructor.
   *
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   The event.
   */
  public function __construct(Event $event) {
    $this->event = $event;
  }

  /**
   * Check to see if the propagation has stopped.
   *
   * @return bool
   *   Is the propagation stopped.
   */
  public function isPropagationStopped(): bool {
    return $this->event->isPropagationStopped();
  }

  /**
   * Stop propagation og the event.
   */
  public function stopPropagation(): void {
    $this->event->stopPropagation();
  }

  /**
   * Proxies all method calls to the original event.
   */
  public function __call($method, $arguments) {
    return $this->event->{$method}(...$arguments);
  }

}
