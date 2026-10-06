<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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
        $stored = is_array($value) ? json_encode($value) : (string) $value;

        /*
         * A password or an API key is written down encrypted.
         *
         * These are the shop's SMTP password and its DigitalOcean secret —
         * things that, in plain text, turn a glance at the database, a backup
         * left on a laptop, or a hosting support ticket into somebody else
         * sending mail as the shop. Encrypted with the application key, so a
         * copy of the table on its own is no use to anybody.
         */
        if ($type === 'secret' && $stored !== '') {
            $stored = Crypt::encryptString($stored);
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
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
            'secret'      => $this->plainSecret(),
            default       => (string) $this->value,
        };
    }

    /**
     * A secret, read back.
     *
     * An empty string rather than an exception when it will not decrypt: the
     * one way that happens is APP_KEY having changed, and a shop whose key
     * changed should find its mail quietly not sending and a blank box in
     * Settings — not every page of the admin dying.
     */
    private function plainSecret(): string
    {
        $value = (string) $this->value;

        if ($value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return '';
        }
    }
}
