<?php declare(strict_types=1);

namespace Models\Core;

interface DBCacheInterface
{
    public static function get(string $type, string $uniqueProperty) : bool|array;
    public static function create(mixed $value, string $expiration, string $type, string $uniqueProperty) : string;
    public static function update(mixed $value, string $expiration, string $type, string $uniqueProperty) : int;
    public static function delete(string $type, string $uniqueProperty) : int;
}
