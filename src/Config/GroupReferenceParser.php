<?php
namespace Juzdy\Config;

class GroupReferenceParser extends AbstractParser
{
    private const EXTENDS_PATTERN = '/^@extends\(([^)]+)\)$/';
    private const GROUP_PATTERN = '/^@\.{3}\(([^)]+)\)$/';

    /**
     * Parses group references in the form of "@extends(group.key)" or "@...(group.key)".
     * Supports relative references using "./" and "../" syntax.
     *
     * @param mixed $value The value to parse.
     * @param ParseContext $context The context of the current parsing operation, including the current path and key.
     * @return ParseResult The result of the parsing operation, indicating whether it was handled
     */
    public function parse(mixed $value, ParseContext $context): ParseResult
    {
        if (!is_string($value)) {
            return ParseResult::notHandled($value);
        }

        $refKey = $this->matchReference($value);
        if ($refKey === null) {
            return ParseResult::notHandled($value);
        }

        $refKey = $this->resolveKey($refKey, $context);
        $resolved = $this->getConfig()->get($refKey);
        if (!is_array($resolved)) {
            throw new \RuntimeException("Config group reference must resolve to an array: " . $value);
        }

        $pathOverride = $refKey === '' ? [] : explode('.', $refKey);

        return ParseResult::handled($resolved, true, true, $pathOverride);
    }

    /**
     * Resolves a reference key, handling both absolute and relative references.
     * Relative references can use "./" to refer to the current level and "../" to refer to parent levels.
     * @param string $refKey The reference key to resolve.
     * @param ParseContext $context The context of the current parsing operation, including the current path and key.
     * 
     * @return string The resolved absolute reference key.
     */
    private function matchReference(string $value): ?string
    {
        if (preg_match(self::EXTENDS_PATTERN, $value, $matches)) {
            return $matches[1];
        }

        if (preg_match(self::GROUP_PATTERN, $value, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
