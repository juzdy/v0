<?php
namespace Juzdy\Config;

class ParseResult
{
    public bool $handled;
    public mixed $value;
    public bool $expand;
    public bool $stop;
    public ?array $pathOverride;

    public function __construct(bool $handled, mixed $value, bool $expand = false, bool $stop = false, ?array $pathOverride = null)
    {
        $this->handled = $handled;
        $this->value = $value;
        $this->expand = $expand;
        $this->stop = $stop;
        $this->pathOverride = $pathOverride;
    }

    public static function notHandled(mixed $value): self
    {
        return new self(false, $value, false, false, null);
    }

    public static function handled(mixed $value, bool $expand = false, bool $stop = false, ?array $pathOverride = null): self
    {
        return new self(true, $value, $expand, $stop, $pathOverride);
    }
}
