<?php
namespace Juzdy\Debug;

class Debug
{
    private static ?DumperInterface $dumper = null;

    public static function dump($data, bool $exit = false): void
    {
        echo self::getDumper()->dump($data);

        if ($exit) {
            exit;
        }
    }

    public static function setDumper(DumperInterface $dumper): void
    {
        self::$dumper = $dumper;
    }

    private static function getDumper(): DumperInterface
    {
        if (self::$dumper === null) {
            self::$dumper = new HtmlDumper();
        }

        return self::$dumper;
    }
}