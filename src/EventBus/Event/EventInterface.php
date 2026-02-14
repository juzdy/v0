<?php
namespace Juzdy\EventBus\Event;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends StoppableEventInterface
{
    /**
     * Attaches a property to the event.
     *
     * @param string|array $nameOrMap The property name or an associative array of properties
     * @param mixed $value The property value (if $nameOrMap is a string)
     * 
     * @return static The event instance
     */
    public function attach(string|array $nameOrMap, mixed $value = null): static;

    /**
     * Creates a clone of the event instance with the given property attached.
     *
     * @param string|array $nameOrMap The property name or an associative array of properties
     * @param mixed $value The property value (if $nameOrMap is a string)
     * 
     * @return static A new event instance with the property attached
     */
    public function with(string|array $nameOrMap, mixed $value = null): static;

    /**
     * Gets a property value from the event.
     *
     * @param string $name The property name
     * @return mixed The property value
     */
    public function get(string $name): mixed;

    /**
     * Checks if the event propagation has been stopped.
     *
     * @return bool True if propagation is stopped, false otherwise
     */
    public function isPropagationStopped(): bool;

    /**
     * Stops the event propagation.
     *
     * @return static The event instance
     */
    public function stopPropagation(): static;
}