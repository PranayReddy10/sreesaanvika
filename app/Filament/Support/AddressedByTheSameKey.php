<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Makes a resource's links point where it will actually look.
 *
 * Filament builds a record's address out of the model's own route key, which
 * here is the slug for a saree and the number for an order, because that is
 * what the shop's public addresses are made of. A resource that sets
 * $recordRouteKeyName then looks the record up by something else entirely —
 * and nothing in Filament reconciles the two, so every Edit and View link on
 * those resources pointed at an address where nothing could be found, and
 * answered 404.
 *
 * Used by a resource that sets $recordRouteKeyName. Where none is set this
 * changes nothing at all.
 */
trait AddressedByTheSameKey
{
    /**
     * @param  array<mixed>  $parameters
     */
    public static function getUrl(
        ?string $name = null,
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        $key = static::getRecordRouteKeyName();

        if ($key !== null && (($parameters['record'] ?? null) instanceof Model)) {
            $parameters['record'] = $parameters['record']->getAttribute($key);
        }

        return parent::getUrl(
            $name,
            $parameters,
            $isAbsolute,
            $panel,
            $tenant,
            $shouldGuessMissingParameters,
            $configuration,
        );
    }
}
