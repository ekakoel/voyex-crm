<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class SchemaInspector
{
    /**
     * @var array<string, bool>
     */
    private static array $tableCache = [];

    /**
     * @var array<string, bool>
     */
    private static array $columnCache = [];

    public static function hasTable(string $table): bool
    {
        if (! array_key_exists($table, self::$tableCache)) {
            self::$tableCache[$table] = Schema::hasTable($table);
        }

        return self::$tableCache[$table];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (! array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = self::hasTable($table) && Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }
}
