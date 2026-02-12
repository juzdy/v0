<?php
namespace Juzdy\Config;

use Juzdy\Config\ParserInterface;
use Juzdy\Config\Repository;

/**
 * Class Config
 *
 * A simple configuration management class that allows loading, retrieving,
 * and setting configuration values from PHP files. Supports dynamic references
 * within configuration values.
 * No overengineering, just straightforward configuration handling.
 */
class Config
{
    private static ?Repository $instance = null;

    /**
     * Initialize configuration from a file.
     *
     * @param string $files glob pattern to the configuration files
     */
    public static function init(string $files): void
    {
        static::instance()->init($files);
    }

    /**
     * Load configuration data from a file.
     *
     * @param string $file Path to the configuration file.
     * @return array The loaded configuration data.
     * @throws \RuntimeException If the file does not exist or cannot be loaded.
     */
    public static function load(string $file): array
    {
        return static::instance()->load($file);
    }

    /**
     * Parse configuration values for dynamic references.
     * This method processes the configuration data recursively, applying registered parsers to handle dynamic references.
     *
     * @todo refacor to use a more robust templating approach: e.g., using a plugin system
     * @todo Improve performance by caching parsed values
     * @todo Handle circular references
     * @todo Add support for default values in references
     * @todo Add support for nested references
     */
    /**
     * Register a parser plugin.
     */
    public static function registerParser(ParserInterface $parser, bool $prepend = false): void
    {
        static::instance()->registerParser($parser, $prepend);
    }

    /**
     * Replace all parser plugins.
     *
     * @param ParserInterface[] $parsers
     */
    public static function setParsers(array $parsers): void
    {
        static::instance()->setParsers($parsers);
    }

    /**
     * Get a configuration value by key.
     *
     * This method allows you to retrieve a configuration value using a dot notation key.
     * For example, if the configuration is structured as ['database' => ['host' => 'localhost']],
     * you can retrieve the host with Config::get('database.host').
     *
     * @param string $key The configuration key in dot notation.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The configuration value or the default value if not found.
     */
    public static function get(string $key, $default = null)
    {
        return static::instance()->get($key, $default);
    }

    /**
     * Set a configuration value by key.
     *
     * @param string $key The configuration key.
     * @param mixed $value The value to set.
     */
    public static function set(string $key, $value): void
    {
        static::instance()->set($key, $value);
    }

    public static function all(): array
    {
        return static::instance()->all();
    }

    /**
     * Clear the configuration data.
     */
    public static function clear(): void
    {
        static::instance()->clear();
    }

    private static function instance(): Repository
    {
        if (static::$instance === null) {
            static::$instance = new Repository();
        }

        return static::$instance;
    }

}