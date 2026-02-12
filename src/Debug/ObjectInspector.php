<?php
namespace Juzdy\Debug;

use ReflectionObject;
use ReflectionProperty;

class ObjectInspector
{
    public static function toArray(object $object): array
    {
        $result = [];
        $reflection = new ReflectionObject($object);

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            $label = self::formatPropertyLabel($property);

            if (!$property->isInitialized($object)) {
                $result[$label] = new UninitializedValue();
                continue;
            }

            $property->setAccessible(true);
            $value = $property->isStatic() ? $property->getValue() : $property->getValue($object);
            $result[$label] = $value;
        }

        foreach (get_object_vars($object) as $name => $value) {
            $label = 'dynamic $' . $name;
            if (!array_key_exists($label, $result)) {
                $result[$label] = $value;
            }
        }

        return $result;
    }

    private static function formatPropertyLabel(ReflectionProperty $property): string
    {
        $visibility = 'public';
        if ($property->isProtected()) {
            $visibility = 'protected';
        } elseif ($property->isPrivate()) {
            $visibility = 'private';
        }

        $static = $property->isStatic() ? ' static' : '';

        return $visibility . $static . ' $' . $property->getName();
    }
}
