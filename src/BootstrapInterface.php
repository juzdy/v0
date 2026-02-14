<?php
namespace Juzdy;

use Juzdy\App\AppInterface;

interface BootstrapInterface
{
    /**
     * Configure the application with the given configuration path.
     * 
     * @param string $configPath Path to the configuration directory.
     * 
      * @return static
     */
    public function configure(string $configPath): static;

    /**
     * Get the application instance.
     */
    public function app(): AppInterface;
}