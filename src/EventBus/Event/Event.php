<?php
namespace Juzdy\EventBus\Event;

use Juzdy\EventBus\Event\ContextInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Event implements EventInterface
{
    
    protected bool $propagationStopped = false;

    /**
     * @param array $properties Initial properties for the event
     */
    public function __construct(
        protected ContextInterface $context, 
        //protected EventDispatcherInterface $dispatcher
        )
    {
    }

    /**
     * Clone magic method to ensure deep copy of context.
     */
    public function __clone()
    {
        $this->context = clone $this->context;
    }

    /**
     * Get the context instance.
     *
     * @return ContextInterface The context instance
     */
    protected function getContext(): ContextInterface
    {
        return $this->context;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(string|array $nameOrMap, mixed $value = null): static
    {
        if (is_array($nameOrMap)) {
            foreach ($nameOrMap as $name => $value) {
                $this->getContext()->set($name, $value);
            }
        } else {
            $this->getContext()->set($nameOrMap, $value);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function with(string|array $nameOrMap, mixed $value = null): static
    {
        $new = clone $this;
        $new->attach($nameOrMap, $value);

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): mixed
    {
        return $this->getContext()->get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * {@inheritDoc}
     */
    public function stopPropagation(): static
    {
        $this->propagationStopped = true;
        return $this;
    }
}