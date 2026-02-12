<?php
namespace Juzdy\Config;

class ReferenceParser extends AbstractParser
{
    private const PATTERN = '/@\(([^)]+)\)/';

    /**
     * Parses a string for configuration references in the format @(key).
     * If the value is not a string or does not contain any references, it returns not handled.
     *
     * @param mixed $value The value to parse.
     * @param ParseContext $context The context of the parsing operation.
     * @return ParseResult The result of the parsing operation.
     */
    public function parse(mixed $value, ParseContext $context): ParseResult
    {
        if (!is_string($value) || strpos($value, '@(') === false) {
            return ParseResult::notHandled($value);
        }

        $resolved = preg_replace_callback(self::PATTERN, function ($matches) use ($context) {
            $refKey = $this->resolveKey($matches[1], $context);
            return $this->getConfig()->get($refKey) ?? $matches[0];
        }, $value);

        return ParseResult::handled($resolved);
    }


}
