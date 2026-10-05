@php use App\Support\Shop; @endphp

<div class="od-wrap py-10 md:py-14">
    <h1 class="font-display text-3xl md:text-5xl">Your bag</h1>

    @if ($notice)
        <p wire:key="notice-{{ md5($notice) }}" class="mt-4 text-sm text-[color:var(--color-marigold)]">{{ $notice }}</p>
    @endif

    @if (! $cart || $cart->items->isEmpty())
        <div class="od-card mt-8 p-12 text-center">
            <p class="font-head text-2xl">There is nothing in it yet</p>
            <p class="mt-3 text-ink-muted">Twelve designs are waiting, each in one or two shades.</p>
            <a href="{{ route('shop') }}" class="od-btn od-btn-gold mt-7">See the sarees</a>
        </div>
    @else
        @php $away = $totals->awayFromFreeShipping($threshold); @endphp

        @if ($threshold > 0)
            <div class="mt-7 od-card p-4">
                @if ($away > 0)
                    <p class="text-sm text-ink-soft">
                        Add {{ Shop::money($away) }} more and delivery is free.
                    </p>
                @else
                    <p class="text-sm text-gold-light">Delivery is on us.</p>
                @endif

                <div class="mt-2.5 h-1 rounded-full bg-[color:var(--color-surface-3)] overflow-hidden">
                    <div class="h-full bg-gold transition-all duration-500"
                         style="width: {{ min(100, $threshold > 0 ? round((($threshold - $away) / $threshold) * 100) : 100) }}%"></div>
                </div>
            </div>
        @endif

        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_380px]">
            <div class="divide-y divide-[color:var(--color-line-soft)]">
                @foreach ($cart->items as $item)
                    @php
                        $product = $item->product;
                        $image = $product?->firstImage($item->colourway);
                        $unit = $product?->priceFor($item->colourway) ?? 0;
                    @endphp

                    <article wire:key="line-{{ $item->id }}" class="flex gap-4 py-6 first:pt-0">
                        <a href="{{ $product ? route('product', $product) : '#' }}" class="shrink-0 w-24 md:w-28">
                            @if ($image)
                                <img src="{{ $image->url }}" alt="{{ $product->name }}"
                                     class="od-shot rounded-md" width="200" height="266" loading="lazy">
                            @endif
                        </a>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h2 class="font-head text-lg leading-snug">
                                        <a href="{{ $product ? route('product', $product) : '#' }}" class="hover:text-gold-light transition">
                                            {{ $product?->name ?? 'This piece is no longer sold' }}
                                        </a>
                                    </h2>

                                    @if ($item->colourway)
                                        <p class="mt-1 flex items-center gap-2 text-sm text-ink-muted">
                                            <span class="w-3 h-3 rounded-full border border-[color:var(--color-line)]"
                                                  style="background: {{ $item->colourway->hex ?: '#555' }}"></span>
                                            {{ $item->colourway->name }}
                                        </p>
                                    @endif

                                    @if ($product?->sku)
                                        <p class="mt-1 text-xs text-ink-faint">Design {{ $product->sku }}</p>
                                    @endif
                                </div>

                                <button type="button" wire:click="removeLine({{ $item->id }})"
                                        class="p-1.5 text-ink-faint hover:text-ink transition shrink-0"
                                        aria-label="Take this out">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-4 flex-wrap">
                                <div class="flex items-center rounded-full border border-[color:var(--color-line-soft)]">
                                    <button type="button"
                                            wire:click="setQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                            class="px-3.5 py-2 text-ink-muted hover:text-ink"
                                            aria-label="One fewer">−</button>

                                    <span class="w-8 text-center text-sm"
                                          wire:loading.class="opacity-40"
                                          wire:target="setQuantity({{ $item->id }}, {{ $item->quantity + 1 }}),setQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                    >{{ $item->quantity }}</span>

                                    <button type="button"
                                            wire:click="setQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                            class="px-3.5 py-2 text-ink-muted hover:text-ink"
                                            aria-label="One more">+</button>
                                </div>

                                <p class="text-right">
                                    <span class="text-lg">{{ Shop::money($unit * $item->quantity) }}</span>
                                    @if ($item->quantity > 1)
                                        <span class="block text-xs text-ink-faint">{{ Shop::money($unit) }} each</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="lg:sticky lg:top-28 h-fit">
                <div class="od-card p-6">
                    <h2 class="font-head text-xl">What it comes to</h2>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Sarees ({{ $totals->items }})</dt>
                            <dd>{{ Shop::money($totals->subtotal) }}</dd>
                        </div>

                        @if ($totals->offerTotal > 0)
                            <div class="flex justify-between text-gold-light">
                                <dt>
                                    Offers
                                    @if ($totals->offers && $totals->offers->offerNameByKey)
                                        <span class="block text-xs text-ink-muted mt-0.5">
                                            {{ collect($totals->offers->offerNameByKey)->unique()->implode(', ') }}
                                        </span>
                                    @endif
                                </dt>
                                <dd>−{{ Shop::money($totals->offerTotal) }}</dd>
                            </div>
                        @endif

                        @if ($totals->couponTotal > 0)
                            <div class="flex justify-between text-gold-light">
                                <dt>Code {{ $totals->couponCode }}</dt>
                                <dd>−{{ Shop::money($totals->couponTotal) }}</dd>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Delivery</dt>
                            <dd>{{ $totals->shippingTotal > 0 ? Shop::money($totals->shippingTotal) : 'Free' }}</dd>
                        </div>
                    </dl>

                    <div class="od-rule my-5"></div>

                    <div class="flex items-baseline justify-between">
                        <span class="font-head text-lg">Total</span>
                        <span class="font-head text-2xl">{{ Shop::money($totals->grandTotal) }}</span>
                    </div>

                    @if ($totals->savings() > 0)
                        <p class="mt-2 text-sm text-gold">You saved {{ Shop::money($totals->savings()) }}.</p>
                    @endif

                    <a href="{{ route('checkout') }}" class="od-btn od-btn-gold w-full mt-6">Go to checkout</a>
                    <a href="{{ route('shop') }}" class="od-btn od-btn-ghost w-full mt-3">Keep looking</a>
                </div>

                <div class="od-card p-6 mt-5">
                    @if ($totals->couponCode)
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm">
                                <span class="text-gold-light font-medium">{{ $totals->couponCode }}</span>
                                <span class="text-ink-muted">applied</span>
                            </p>
                            <button type="button" wire:click="clearCoupon" class="text-xs uppercase tracking-wider text-ink-faint hover:text-ink">Remove</button>
                        </div>
                    @else
                        <form wire:submit="applyCoupon">
                            <label for="coupon" class="od-label">Have a code?</label>
                            <div class="flex gap-2">
                                <input id="coupon" wire:model="coupon" type="text" class="od-input py-2.5 uppercase" placeholder="OJASVI10">
                                <button type="submit" class="od-btn od-btn-ghost px-5 py-2.5 shrink-0">Use</button>
                            </div>
                        </form>

                        @if ($couponError)
                            <p class="mt-2 text-sm text-[color:var(--color-marigold)]">{{ $couponError }}</p>
                        @endif
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
