<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Shop settings, one row per key.
 *
 * Read constantly and written rarely, so the whole table is cached as one map
 * and the cache is dropped whenever a row changes. Settings are plain data —
 * nothing here is ever executed.
 */
class Setting extends Model
{
    protected $guarded = [];

    public const CACHE_KEY = 'ojasvi.settings';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function all_values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->cast()])
            ->all());
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        return static::all_values()[$key] ?? $fallback;
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type'  => $type,
                'group' => $group,
            ]
        );
    }

    protected function cast(): mixed
    {
        return match ($this->type) {
            'bool'        => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'int'         => (int) $this->value,
            'money'       => (float) $this->value,
            'json'        => json_decode((string) $this->value, true) ?: [],
            default       => (string) $this->value,
        };
    }
}
