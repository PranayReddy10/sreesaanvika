<?php

namespace App\Services;

/**
 * What the offer engine worked out. Plain data — it decides nothing.
 */
class OfferResult
{
    public float $subtotal = 0.0;
    public float $discount = 0.0;
    public float $total = 0.0;

    /** @var array<string, float> savings per bag line */
    public array $savingsByKey = [];

    /** @var array<string, int> how many units of a line went free */
    public array $freeUnitsByKey = [];

    /** @var array<string, string> which offer did it */
    public array $offerNameByKey = [];

    /** @var array<string, int> */
    public array $offerIdByKey = [];

    /** @var array<int, array<string, mixed>> how each offer is doing */
    public array $progress = [];

    public function savedOn(string $key): float
    {
        return $this->savingsByKey[$key] ?? 0.0;
    }

    public function freeUnits(string $key): int
    {
        return $this->freeUnitsByKey[$key] ?? 0;
    }
}
