<?php

namespace Juzdy\Configuration;

use Juzdy\Configuration\Resolver\EnvResolver;
use Juzdy\Configuration\Resolver\ExtendsResolver;
use Juzdy\Configuration\Resolver\ReferenceResolver;
use Juzdy\Configuration\Resolver\ResolverInterface;
use Juzdy\Configuration\Resolver\ResolverManager;

/**
 * Loads configuration arrays from PHP files and provides resolved access via dot notation.
 */
class Configuration
{
	/**
	 * @var array<string, mixed>
	 */
	private array $data = [];
	/**
	 * @var ResolverManager
	 */
	private ?ResolverManager $resolverManager = null;
	/**
	 * @var bool
	 */
	private bool $resolved = false;

	/**
	 * @param string $configDir Base directory that contains configuration files.
	 * @param bool $resolveOnLoad Resolve all values after loading.
	 * @param ResolverManager|null $resolverManager Resolver manager instance.
	 */
	public function __construct(
		string $configDir,
		bool $resolveOnLoad = true,
		?ResolverManager $resolverManager = null
	)
	{
		$this->load($configDir);

		$this->registerDefaultResolvers();

		if ($resolveOnLoad) {
			$this->resolveAll();
		}
	}

	/**
	 * Check if a configuration key exists after resolution.
	 *
	 * @param string $key Dot-separated key.
	 * @return bool
	 */
	public function has(string $key): bool
	{
		$this->ensureResolved();

		return $this->hasRaw($key);
	}

	/**
	 * Get a resolved configuration value by dot path.
	 *
	 * @param string $key Dot-separated key.
	 * @param mixed $default Default value when key is missing.
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		$this->ensureResolved();

		return $this->getRaw($key, $default);
	}

	/**
	 * Set a configuration value using dot notation.
	 *
	 * @param string $key Dot-separated key.
	 * @param mixed $value Value to set.
	 * @return void
	 */
	public function set(string $key, mixed $value): void
	{
		$parts = $this->splitPath($key);
		if ($parts === []) {
			return;
		}

		$data = &$this->getData();
		$lastIndex = count($parts) - 1;
		foreach ($parts as $index => $part) {
			if ($index === $lastIndex) {
				$data[$part] = $value;
				break;
			}

			if (!isset($data[$part]) || !is_array($data[$part])) {
				$data[$part] = [];
			}

			$data = &$data[$part];
		}

		$this->resolveFailed();
	}

	/**
	 * Fluent setter for configuration values.
	 *
	 * @param string $key Dot-separated key.
	 * @param mixed $value Value to set.
	 * @return static
	 */
	public function with(string $key, mixed $value): static
	{
		$this->set($key, $value);

		return $this;
	}

	/**
	 * Return all resolved configuration values.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		$this->ensureResolved();

		return $this->getData();
	}

	/**
	 * Register a resolver for dynamic values.
	 *
	 * @param ResolverInterface $resolver Resolver instance.
	 * @param int $priority Higher runs first.
	 * @return void
	 */
	public function registerResolver(ResolverInterface $resolver, int $priority = 0): void
	{
		$this->getResolverManager()->register($resolver, $priority);
		$this->resolveFailed();
	}

	/**
	 * Fluent resolver registration.
	 *
	 * @param ResolverInterface $resolver Resolver instance.
	 * @param int $priority Higher runs first.
	 * @return static
	 */
	public function withResolver(ResolverInterface $resolver, int $priority = 0): static
	{
		$this->registerResolver($resolver, $priority);

		return $this;
	}

	/**
	 * Resolve all configuration values in place.
	 *
	 * @return void
	 */
	public function resolveAll(): void
	{
		if ($this->isResolved()) {
			return;
		}

		$data = &$this->getData();
		$this->getResolverManager()->resolve($data);
		$this->resolved();
	}

	/**
	 * Fluent resolver execution.
	 *
	 * @return static
	 */
	public function resolve(): static
	{
		$this->resolveAll();

		return $this;
	}

	/**
	 * Resolve a single value within the configuration context.
	 *
	 * @param string $path Dot-separated path to resolve against.
	 * @param mixed $value Value to resolve.
	 * @return mixed
	 */
	public function resolveValue(string $path, mixed $value): mixed
	{
		return $this->getResolverManager()->resolveValue($path, $value);
	}

	/**
	 * Check if a configuration key exists without forcing resolution.
	 *
	 * @param string $key Dot-separated key.
	 * @return bool
	 */
	public function hasRaw(string $key): bool
	{
		$parts = $this->splitPath($key);
		$found = false;
		$this->traverse($parts, $found);

		return $found;
	}

	/**
	 * Get a configuration value without forcing resolution.
	 *
	 * @param string $key Dot-separated key.
	 * @param mixed $default Default value when key is missing.
	 * @return mixed
	 */
	public function getRaw(string $key, mixed $default = null): mixed
	{
		$parts = $this->splitPath($key);
		$found = false;
		$value = $this->traverse($parts, $found);

		return $found ? $value : $default;
	}

	/**
	 * Ensure configuration values are resolved.
	 *
	 * @return void
	 */
	private function ensureResolved(): void
	{
		if (!$this->isResolved()) {
			$this->resolveAll();
		}
	}

	/**
	 * Register the built-in resolvers.
	 *
	 * @return void
	 */
	private function registerDefaultResolvers(): void
	{
		$this->getResolverManager()
			->register(new ExtendsResolver($this), 200)
			->register(new EnvResolver($this), 110)
			->register(new ReferenceResolver($this), 100);
	}

	/**
	 * Load configuration data from disk into the internal store.
	 *
	 * @param string $configDir Base directory that contains configuration files.
	 * @return void
	 */
	private function load(string $configDir): void
	{
		$baseDir = rtrim($configDir, DIRECTORY_SEPARATOR);
		if ($baseDir === '' || !is_dir($baseDir)) {
			return;
		}

		foreach ($this->collectConfigFiles($baseDir) as $file) {
			$config = include $file;
			if (!is_array($config)) {
				continue;
			}

			$scope = $this->getScopePath($baseDir, $file);
			$this->mergeConfig($scope, $config);
		}

		$this->resolveFailed();
	}

	/**
	 * @param string $baseDir Base directory for config files.
	 * @return string[]
	 */
	private function collectConfigFiles(string $baseDir): array
	{
		$files = [];
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $fileInfo) {
			if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'php') {
				$files[] = $fileInfo->getPathname();
			}
		}

		usort($files, function (string $left, string $right) use ($baseDir): int {
			$depthLeft = $this->getDirectoryDepth($baseDir, $left);
			$depthRight = $this->getDirectoryDepth($baseDir, $right);

			if ($depthLeft === $depthRight) {
				return strcmp($left, $right);
			}

			return $depthRight <=> $depthLeft;
		});

		return $files;
	}

	/**
	 * Calculate directory nesting depth for sorting.
	 *
	 * @param string $baseDir Base directory for config files.
	 * @param string $file Full file path.
	 * @return int
	 */
	private function getDirectoryDepth(string $baseDir, string $file): int
	{
		$relativeDir = trim(str_replace($baseDir, '', dirname($file)), DIRECTORY_SEPARATOR);
		if ($relativeDir === '') {
			return 0;
		}

		return count(array_filter(explode(DIRECTORY_SEPARATOR, $relativeDir), 'strlen'));
	}

	/**
	 * Convert a file path to a dot-separated configuration scope.
	 *
	 * @param string $baseDir Base directory for config files.
	 * @param string $file Full file path.
	 * @return string
	 */
	private function getScopePath(string $baseDir, string $file): string
	{
		$relativeDir = trim(str_replace($baseDir, '', dirname($file)), DIRECTORY_SEPARATOR);
		if ($relativeDir === '') {
			return '';
		}

		return implode('.', array_filter(explode(DIRECTORY_SEPARATOR, $relativeDir), 'strlen'));
	}

	/**
	 * Merge configuration into the stored data at the given scope.
	 *
	 * @param string $scope Dot-separated scope.
	 * @param array<string, mixed> $config Configuration array to merge.
	 * @return void
	 */
	private function mergeConfig(string $scope, array $config): void
	{
		if ($scope === '') {
			$this->setData(array_replace_recursive($this->getData(), $config));
			return;
		}

		$parts = $this->splitPath($scope);
		$target = &$this->getData();
		foreach ($parts as $part) {
			if (!isset($target[$part]) || !is_array($target[$part])) {
				$target[$part] = [];
			}
			$target = &$target[$part];
		}

		$target = array_replace_recursive($target, $config);
	}

	/**
	 * @param string $key Dot-separated key.
	 * @return string[]
	 */
	private function splitPath(string $key): array
	{
		$key = trim($key);
		if ($key === '') {
			return [];
		}

		return array_values(array_filter(explode('.', $key), 'strlen'));
	}

	/**
	 * Get resolver manager instance, creating it when needed.
	 *
	 * @return ResolverManager
	 */
	private function getResolverManager(): ResolverManager
	{
		return $this->resolverManager ??= new ResolverManager();
	}

	/**
	 * Check whether configuration values are resolved.
	 *
	 * @return bool
	 */
	private function isResolved(): bool
	{
		return $this->getResolvedFlag();
	}

	/**
	 * Mark configuration as resolved.
	 *
	 * @return void
	 */
	private function resolved(): void
	{
		$this->setResolvedFlag(true);
	}

	/**
	 * Mark configuration as unresolved after changes.
	 *
	 * @return void
	 */
	private function resolveFailed(): void
	{
		$this->setResolvedFlag(false);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function &getData(): array
	{
		return $this->data;
	}

	/**
	 * @param array<string, mixed> $data Configuration data.
	 * @return void
	 */
	private function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * @return bool
	 */
	private function getResolvedFlag(): bool
	{
		return $this->resolved;
	}

	/**
	 * @param bool $resolved Resolved flag.
	 * @return void
	 */
	private function setResolvedFlag(bool $resolved): void
	{
		$this->resolved = $resolved;
	}

	/**
	 * Traverse the configuration tree using dot-separated parts.
	 *
	 * @param string[] $parts
	 * @param bool $found Output flag for successful lookup.
	 * @return mixed
	 */
	private function traverse(array $parts, bool &$found): mixed
	{
		$found = false;
		if ($parts === []) {
			return null;
		}

		$value = $this->getData();
		foreach ($parts as $part) {
			if (is_array($value) && array_key_exists($part, $value)) {
				$value = $value[$part];
				continue;
			}

			return null;
		}

		$found = true;

		return $value;
	}
}
