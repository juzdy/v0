<?php
namespace Juzdy;


use Juzdy\Composer\Composer;
use Juzdy\Container\Attribute\Shared;
use Juzdy\Container\Container;
use Juzdy\Container\Plugin\Resolver\Concrete;
use Juzdy\Container\Plugin\Resolver\InterfaceConvention;
use Juzdy\Container\Plugin\Resolver\WireResolver;
use Juzdy\EventBus\Event\EventInterface;
use Juzdy\Http\Http;
use Juzdy\Plugin\Manager\ResolveManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Application bootstrapper
 *
 * @package Juzdy
 */

// #[PropagatePreference(
//     [
//         EventDispatcherInterface::class => \Juzdy\EventBus\EventDispatcher::class,
//     ]
// )]
#[Shared]
class Bootstrap implements BootstrapInterface
{

    private ?ContainerInterface $container = null;
    private ?AppInterface $app = null;

    /**
     * @param AppInterface $app Resolved application instance
     */
    public function __construct(
        private string $base
        )
    {
    }

    private function base(): string
    {
        return $this->base;
    }

    /**
     * Get the dependency injection container
     */
    private function container(): ContainerInterface
    {
        return $this->container ??= new Container();
    }

    /**
     * Get the application instance
     */
    private function app(): AppInterface
    {
        return $this->app ??= $this->container()->get(Http::class);
    }

    /**
     * Boot the application
     */
    public function boot(): void
    {
        Config::init($this->base() . '/etc/config/*.php');

        $this->plugContainer();  

        $app = $this->app();

        $this->warm();

        $app->run();

    }

    /**
     * Register container plugins
     * 
     * This method registers essential plugins to the container's resolve manager, which is responsible for resolving dependencies and managing object lifecycles.
     * The registered plugins include:
     * - Concrete: A plugin that resolves concrete classes without interfaces.
     * - WireResolver: A plugin that automatically wires dependencies based on constructor signatures.
     * - InterfaceConvention: A plugin that resolves interfaces based on naming conventions (e.g., AppInterface resolves to App).
     * 
     * By registering these plugins, the container can automatically resolve a wide range of dependencies without requiring explicit configuration for each one.
     * This promotes a convention-over-configuration approach, allowing for faster development and easier maintenance.
     * Additional plugins can be registered here as needed to support custom resolution logic or specific use cases.
     * 
     * @return void
     */
    protected function plugContainer()
    {
        $this->container()->get(ResolveManagerInterface::class)
            ->register(new Concrete())
            ->register(new WireResolver())
            ->register(new InterfaceConvention())
            ;

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
    protected function warm(): void
    {
        if (!Config::get('bootstrap.discover', false)) {
            return;
        }

        $psr4 = Composer::namespaces();

        foreach ($psr4 as $namespace) {
            $packageBootstrapClass = '\\' . $namespace . 'Juzdy\Pac';
            try {

                if ($this instanceof $packageBootstrapClass) {
                    continue;
                }

                if (!is_a($packageBootstrapClass, BootstrapInterface::class, true)) {
                    continue;
                }
                $pack = $this->container()->get($packageBootstrapClass);
                $pack->up($this->app());

            } catch (\Psr\Container\NotFoundExceptionInterface) {
                continue;
            }
        }
    }

}