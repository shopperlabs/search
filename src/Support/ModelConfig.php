<?php

declare(strict_types=1);

namespace Shopper\Search\Support;

final class ModelConfig
{
    /**
     * Resolve a class-keyed config value, walking up the class hierarchy so
     * swapped models (config shopper.models.*) match entries keyed by their
     * base class.
     *
     * @param  class-string  $class
     */
    public static function lookup(string $key, string $class, mixed $default = null): mixed
    {
        foreach ([$class, ...array_values(class_parents($class) ?: [])] as $candidate) {
            $value = config($key.'.'.$candidate);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }
}
