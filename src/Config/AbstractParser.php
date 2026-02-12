<?php
namespace Juzdy\Config;

abstract class AbstractParser implements ParserInterface
{
    protected ConfigResolverInterface $config;

    public function __construct(ConfigResolverInterface $config)
    {
        $this->config = $config;
    }

    protected function getConfig(): ConfigResolverInterface
    {
        return $this->config;
    }

    protected function resolveKey(string $refKey, ParseContext $context): string
    {
        if (!str_starts_with($refKey, '.')) {
            return $refKey;
        }

        $path = $context->path;
        array_pop($path); // current key

        $relative = $this->stripRelativePrefixes($refKey, $path);
        if ($relative === '') {
            return '';
        }

        $segments = array_filter(explode('.', $relative), 'strlen');
        $absolute = array_merge($path, $segments);

        return implode('.', $absolute);
    }

    private function stripRelativePrefixes(string $refKey, array &$path): string
    {
        $relative = $refKey;

        while (true) {
            if (str_starts_with($relative, './')) {
                $relative = substr($relative, 2);
                continue;
            }

            if (str_starts_with($relative, '../')) {
                $relative = substr($relative, 3);
                $this->popParent($path);
                continue;
            }

            break;
        }

        return $relative;
    }

    protected function popParent(array &$path): void
    {
        do {
            $segment = array_pop($path);
        } while ($segment !== null && (is_int($segment) || ctype_digit((string) $segment)));
    }
}
