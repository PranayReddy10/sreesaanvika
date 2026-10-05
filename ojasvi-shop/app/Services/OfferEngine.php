<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Works out what the bag costs once the offers are applied.
 *
 * Two things this is built to get right, both learned the hard way.
 *
 * It is pure: it reads the bag and returns a result, and changes nothing. The
 * WordPress version mutated the cart's own price objects and then had to run
 * twice without compounding, which is a problem that simply does not exist
 * here.
 *
 * And it counts units, not lines. Three of one saree is three units towards a
 * "buy two get one" — a shopper who adds the same piece three times has
 * bought three, however many rows that is.
 */
class OfferEngine
{
    /**
     * @param  Collection<int, array{product: Product, colourway: ?\App\Models\Colourway, quantity: int, key: string}>  $lines
     */
    public function apply(Collection $lines): OfferResult
    {
        $units = $this->explode($lines);

        $result = new OfferResult();
        $result->subtotal = round($units->sum('price'), 2);

        if ($units->isEmpty()) {
            return $result;
        }

        $offers = Offer::live()->with(['products:id', 'categories:id'])->get();

        foreach ($offers as $offer) {
            $eligible = $units->filter(fn (array $u) => $offer->covers($u['product']))->values();

            if ($eligible->isEmpty()) {
                continue;
            }

            $offer->kind === 'tiers'
                ? $this->applyTiers($offer, $eligible, $result)
                : $this->applyBogo($offer, $eligible, $result);
        }

        $result->discount = round(collect($result->savingsByKey)->sum(), 2);
        $result->total    = round(max(0, $result->subtotal - $result->discount), 2);

        return $result;
    }

    /**
     * One entry per unit, because an offer counts pieces and not lines.
     *
     * @return Collection<int, array{key: string, price: float, product: Product}>
     */
    protected function explode(Collection $lines): Collection
    {
        $units = collect();

        foreach ($lines as $line) {
            $price = $line['product']->priceFor($line['colourway'] ?? null);

            for ($i = 0; $i < (int) $line['quantity']; $i++) {
                $units->push([
                    'key'     => $line['key'],
                    'price'   => $price,
                    'product' => $line['product'],
                ]);
            }
        }

        return $units;
    }

    /**
     * Buy X get Y — the cheapest units are the ones that go free.
     */
    protected function applyBogo(Offer $offer, Collection $eligible, OfferResult $result): void
    {
        $group  = $offer->groupSize();
        $have   = $eligible->count();
        $rounds = intdiv($have, $group);

        if (! $offer->repeats) {
            $rounds = min(1, $rounds);
        }

        $give = $rounds * max(1, (int) $offer->get);

        $result->progress[] = [
            'offer'    => $offer,
            'have'     => $have,
            'free'     => $give,
            // How many more pieces earn the next one free. Shown to the
            // shopper, so it has to be the honest remainder.
            'need'     => $give > 0 && ! $offer->repeats ? 0 : ($group - ($have % $group)) % $group,
            'headline' => $offer->headlineText(),
        ];

        if ($give < 1) {
            return;
        }

        $percent = max(0, min(100, (float) $offer->percent)) / 100;

        foreach ($eligible->sortBy('price')->take($give) as $unit) {
            $saved = round($unit['price'] * $percent, 2);

            if ($saved <= 0) {
                continue;
            }

            $key = $unit['key'];
            $result->savingsByKey[$key] = round(($result->savingsByKey[$key] ?? 0) + $saved, 2);
            $result->freeUnitsByKey[$key] = ($result->freeUnitsByKey[$key] ?? 0) + 1;
            $result->offerNameByKey[$key] = $offer->name;
            $result->offerIdByKey[$key]   = $offer->id;
        }
    }

    /**
     * Quantity breaks: take more, each one costs less.
     *
     * The richest break the bag qualifies for wins, not every break it passes,
     * or a large bag would be discounted several times over.
     */
    protected function applyTiers(Offer $offer, Collection $eligible, OfferResult $result): void
    {
        $tiers = $offer->tierList();

        if (! $tiers) {
            return;
        }

        $have    = $eligible->count();
        $reached = null;
        $next    = null;

        foreach ($tiers as $tier) {
            if ($have >= $tier['qty']) {
                $reached = $tier;
            } elseif ($next === null) {
                $next = $tier;
            }
        }

        $result->progress[] = [
            'offer'    => $offer,
            'have'     => $have,
            'free'     => 0,
            'need'     => $next ? $next['qty'] - $have : 0,
            'percent'  => $reached['percent'] ?? 0,
            'headline' => $offer->headlineText(),
        ];

        if (! $reached) {
            return;
        }

        $percent = $reached['percent'] / 100;

        foreach ($eligible as $unit) {
            $saved = round($unit['price'] * $percent, 2);

            if ($saved <= 0) {
                continue;
            }

            $key = $unit['key'];
            $result->savingsByKey[$key] = round(($result->savingsByKey[$key] ?? 0) + $saved, 2);
            $result->offerNameByKey[$key] = $offer->name;
            $result->offerIdByKey[$key]   = $offer->id;
        }
    }
}
