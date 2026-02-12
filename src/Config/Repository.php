<?php
namespace Juzdy\Config;

/**
 * Instance-based configuration repository.
 */
class Repository implements ConfigResolverInterface
{
    /** @var array The configuration data storage */
    private array $data = [];

    /** @var bool Flag to indicate if configuration has been loaded */
    private bool $isLoaded = false;

    /** @var ParserInterface[] */
    private array $parsers = [];

    /** @var bool Flag to indicate if parsers were initialized */
    private bool $parsersInitialized = false;

    /**
     * Initialize configuration from a file.
     *
     * @param string $files glob pattern to the configuration files
     */
    public function init(string $files): void
    {
        foreach (glob($files) as $configFile) {
            $this->load($configFile);
        }
    }

    /**
     * Load configuration data from a file.
     *
     * @param string $file Path to the configuration file.
     * @return array The loaded configuration data.
     * @throws \RuntimeException If the file does not exist or cannot be loaded.
     */
    public function load(string $file): array
    {
        if (!file_exists($file)) {
            throw new \RuntimeException('Config file not found: ' . $file);
        }

        $data = require $file;

        if (!is_array($data)) {
            throw new \RuntimeException('Config file must return an array: ' . $file);
        }

        $this->data = array_merge($this->data, $data);
        $this->isLoaded = true;

        $this->parse();

        return $this->data;
    }

    /**
     * Parse configuration values for dynamic references.
     */
    protected function parse(): void
    {
        $this->data = $this->parseRecursive($this->data);
    }

    private function parseRecursive(array $data, array $parents = []): array
    {
        $isList = $this->isList($data);
        $result = [];

        foreach ($data as $key => $value) {
            $currentPath = array_merge($parents, [$key]);

            if (is_string($value)) {
                $context = new ParseContext($key, $currentPath, $isList);
                $parseResult = $this->applyParsers($value, $context);

                if ($parseResult->handled) {
                    if ($parseResult->expand && is_array($parseResult->value)) {
                        $expandedParents = $parseResult->pathOverride ?? $currentPath;
                        $expanded = $this->parseRecursive($parseResult->value, $expandedParents);

                        if ($isList || is_int($key)) {
                            $result = $this->mergePreserveKeys($result, $expanded);
                            continue;
                        }

                        $value = $expanded;
                    } else {
                        $value = $parseResult->value;
                    }
                }
            }

            if (is_array($value)) {
                $value = $this->parseRecursive($value, $currentPath);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Register a parser plugin.
     */
    public function registerParser(ParserInterface $parser, bool $prepend = false): void
    {
        $this->initParsers();

        if ($prepend) {
            array_unshift($this->parsers, $parser);
            return;
        }

        $this->parsers[] = $parser;
    }

    /**
     * Replace all parser plugins.
     *
     * @param ParserInterface[] $parsers
     */
    public function setParsers(array $parsers): void
    {
        foreach ($parsers as $parser) {
            if (!$parser instanceof ParserInterface) {
                throw new \InvalidArgumentException('All parsers must implement ParserInterface.');
            }
        }

        $this->parsers = array_values($parsers);
        $this->parsersInitialized = true;
    }

    /**
     * @return ParserInterface[]
     */
    private function getParsers(): array
    {
        $this->initParsers();

        return $this->parsers;
    }

    private function initParsers(): void
    {
        if ($this->parsersInitialized) {
            return;
        }

        $this->parsers = [
            new GroupReferenceParser($this),
            new ReferenceParser($this),
        ];
        $this->parsersInitialized = true;
    }

    /**
     * Apply registered parsers to a value.
     *
     * @param mixed $value The value to parse.
     * @param ParseContext $context The context of the parsing operation.
     * @return ParseResult The result of the parsing operation.
     */
    private function applyParsers(mixed $value, ParseContext $context): ParseResult
    {
        $current = $value;
        $handled = false;
        $expand = false;
        $stop = false;
        $pathOverride = null;

        foreach ($this->getParsers() as $parser) {
            $result = $parser->parse($current, $context);

            if ($result->handled) {
                $handled = true;
                $current = $result->value;
                $expand = $result->expand;
                if ($result->pathOverride !== null) {
                    $pathOverride = $result->pathOverride;
                }

                if ($result->stop) {
                    $stop = true;
                    break;
                }
            }
        }

        return new ParseResult($handled, $current, $expand, $stop, $pathOverride);
    }

    private function isList(array $data): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($data);
        }

        $expected = 0;
        foreach ($data as $key => $_value) {
            if ($key !== $expected) {
                return false;
            }
            $expected++;
        }

        return true;
    }

    private function mergePreserveKeys(array $base, array $append): array
    {
        foreach ($append as $appendKey => $appendValue) {
            $base[$appendKey] = $appendValue;
        }

        return $base;
    }

    /**
     * Get a configuration value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isLoaded) {
            throw new \RuntimeException('Configuration not loaded. Please call Config::init() first.');
        }

        $parts = explode('.', $key);
        $value = $this->data;
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set a configuration value by key.
     */
    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $data = &$this->data;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $data[$part] = $value;
            } else {
                if (!isset($data[$part]) || !is_array($data[$part])) {
                    $data[$part] = [];
                }
                $data = &$data[$part];
            }
        }
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     * Clear the configuration data.
     */
    public function clear(): void
    {
        $this->data = [];
        $this->isLoaded = false;
    }
}
