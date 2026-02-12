<?php
namespace Juzdy\Config;

interface ConfigResolverInterface
{
    public function get(string $key, mixed $default = null): mixed;
}
