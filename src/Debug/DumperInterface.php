<?php
namespace Juzdy\Debug;

interface DumperInterface
{
    public function dump(mixed $data): string;
}
