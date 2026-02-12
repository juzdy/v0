<?php
namespace Juzdy\Config;

class ParseContext
{
    public int|string $key;
    public array $path;
    public bool $isList;

    public function __construct(int|string $key, array $path, bool $isList)
    {
        $this->key = $key;
        $this->path = $path;
        $this->isList = $isList;
    }

    public function pathString(): string
    {
        return implode('.', $this->path);
    }
}
