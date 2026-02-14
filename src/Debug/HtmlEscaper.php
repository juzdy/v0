<?php
namespace Juzdy\Debug;

class HtmlEscaper
{
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
