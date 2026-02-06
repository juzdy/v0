<?php
namespace Juzdy;

use Juzdy\Composer\Composer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Juzdy\Container\Attribute\Parameter\Using;
use Juzdy\Container\Attribute\Preference;
use Juzdy\Container\Attribute\PropagatePreference;
use Juzdy\Container\Attribute\Shared;
use Juzdy\EventBus\Event\EventInterface;
use Psr\Container\ContainerInterface;

/**
 * Application bootstrapper
 *
 * @package Juzdy
 */

/*
 * Preference attribute allows specifying that when AppInterface is requested,
 * the Http\Http class should be provided as its implementation.
 */
#[Preference(
    [
        AppInterface::class => \Juzdy\Http\Http::class,
        EventDispatcherInterface::class => \Juzdy\EventBus\EventDispatcher::class,
    ]
)]
// #[PropagatePreference(
//     [
//         EventDispatcherInterface::class => \Juzdy\EventBus\EventDispatcher::class,
//     ]
// )]
#[Shared]
class Bootstrap implements BootstrapInterface
{

    /**
     * @param AppInterface $app Resolved application instance
     */
    public function __construct(
        private ContainerInterface $container,
        private AppInterface $app,
        private EventDispatcherInterface $eventDispatcher,
        #[Using(\Juzdy\Http\Event\BeforeRun::class)]
        private EventInterface $beforeStartEvent
        )
    {
    }

    /**
     * Get the dependency injection container
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the application instance
     */
    protected function getApp(): AppInterface
    {
        return $this->app;
    }

    /**
     * Boot the application
     */
    public function boot(): void
    {
        $this->composerUp();

        $app = $this->getApp();

        $this->getEventDispatcher()
            ->dispatch(
                $this->getBeforeStartEvent()
                    ->attach('app', $app)
            );

        $app->run();
    }

    /**
     * Discover and boot package-specific bootstraps
     * 
     * This method checks for the presence of a Bootstrap class in each PSR-4 namespace defined in composer.json.
     * If such a class exists and implements BootstrapInterface, it is instantiated and its boot() method is called.
     * This allows packages to automatically register their own bootstrapping logic without requiring manual integration.
     * For example, if a package defines a namespace "Vendor\Package", the method will look for a class "Vendor\Package\Bootstrap" and attempt to boot it if it implements the required interface.
     * Use this mechanism to allow packages to self-register their bootstrapping logic, such as event listeners, middleware, or other initialization code.
     * Vendor\Bootstrap may be injected with dependencies via the container, allowing for flexible and decoupled package design.
     * Use Constructor Injection in package bootstraps to access services from the container, such as the event dispatcher, configuration, or other shared services.
     * 
     * Discovery is controlled by the 'bootstrap.discover' configuration option. If set to false, discovery is skipped.
     * The method uses the Composer class to retrieve the list of PSR-4 namespaces and attempts to find a corresponding Bootstrap class in each namespace.
     * If a Bootstrap class is found and implements the required interface, it is resolved from the container and booted.
     * Errors during discovery (e.g., class not found) are caught and ignored, allowing the process to continue with other namespaces.
     * @return void
     */
    protected function composerUp(): void
    {
        if (!Config::get('bootstrap.discover', false)) {
            return;
        }

        $psr4 = Composer::namespaces();

        foreach ($psr4 as $namespace) {
            $packageBootstrapClass = '\\' . $namespace . 'Bootstrap';
            try {

                if ($this instanceof $packageBootstrapClass) {
                    continue;
                }

                if (!is_a($packageBootstrapClass, BootstrapInterface::class, true)) {
                    continue;
                }
                $bootstrap = $this->getContainer()->get($packageBootstrapClass);
                $bootstrap->boot();

            } catch (\Psr\Container\NotFoundExceptionInterface) {
                continue;
            }
        }
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getBeforeStartEvent(): EventInterface
    {
        return $this->beforeStartEvent;
    }

}