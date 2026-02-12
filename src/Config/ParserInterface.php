<?php
namespace Juzdy\Config;

interface ParserInterface
{
    public function parse(mixed $value, ParseContext $context): ParseResult;
}
