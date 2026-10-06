@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@section('title', 'Checkout — ' . Shop::name())

@push('tracking')
@php
    $track = [
        'value'  => round($totals->grandTotal, 2),
        'coupon' => $totals->couponCode,
        'items'  => $cart->items->map(fn ($item) => [
            'item_id'   => $item->product?->sku ?: (string) $item->product_id,
            'item_name' => $item->product?->name,
            'price'     => round($item->product?->priceFor($item->colourway) ?? 0, 2),
            'quantity'  => (int) $item->quantity,
        ])->values(),
    ];
@endphp
<script>odTrack('begin_checkout', {!! \App\Support\Seo::json($track) !!});</script>
@endpush

@section('content')
<div class="od-wrap py-10 md:py-14">
    <h1 class="font-display text-3xl md:text-5xl">Checkout</h1>

    @guest
        <div class="od-card mt-6 p-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-ink-soft">
                Have an account with us? Sign in and your address fills itself in.
            </p>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('sign-in') }}" class="od-btn od-btn-ghost py-2.5 px-5 text-[0.7rem]">Sign in</a>
                <a href="{{ route('join') }}" class="od-btn od-btn-ghost py-2.5 px-5 text-[0.7rem]">Make one</a>
            </div>
        </div>
        <p class="mt-3 text-xs text-ink-faint">You do not need one — carry on below and order as a guest.</p>
    @endguest

    @if (session('bag_error'))
        <div class="mt-6 rounded-[var(--radius-card)] border border-[color:var(--color-maroon)] bg-[color:var(--color-surface)] p-4 text-sm">
            {{ session('bag_error') }}
        </div>
    @endif

    {{-- The cash-on-delivery fee changes the total, so the summary has to
         follow the chosen method. Both figures are worked out on the server;
         the page only picks between them, so what is shown is always what
         will be charged. --}}
    <form method="post" action="{{ route('checkout.place') }}"
          x-data="{ method: '{{ old('method', $online ? 'razorpay' : 'cod') }}' }"
          class="mt-8 grid gap-10 lg:grid-cols-[1fr_380px]">
        @csrf

        <div class="space-y-8">
            <section>
                <h2 class="font-head text-xl">Where it should go</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="od-label">Full name</label>
                        <input id="name" name="name" value="{{ old('name', $address?->name ?? auth()->user()?->name) }}"
                               required autocomplete="name" class="od-input">
                        @error('name')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="od-label">Mobile</label>
                        <input id="phone" name="phone" value="{{ old('phone', $address?->phone ?? auth()->user()?->phone) }}"
                               required inputmode="numeric" autocomplete="tel-national" maxlength="10"
                               placeholder="9000000000" class="od-input">
                        @error('phone')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="od-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}"
                               required autocomplete="email" class="od-input">
                        @error('email')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="line1" class="od-label">Flat, house, street</label>
                        <input id="line1" name="line1" value="{{ old('line1', $address?->line1) }}"
                               required autocomplete="address-line1" class="od-input">
                        @error('line1')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="line2" class="od-label">Area, colony <span class="normal-case text-ink-faint">(optional)</span></label>
                        <input id="line2" name="line2" value="{{ old('line2', $address?->line2) }}"
                               autocomplete="address-line2" class="od-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="landmark" class="od-label">Landmark <span class="normal-case text-ink-faint">(optional)</span></label>
                        <input id="landmark" name="landmark" value="{{ old('landmark', $address?->landmark) }}" class="od-input">
                    </div>

                    <div>
                        <label for="pincode" class="od-label">Pincode</label>
                        <input id="pincode" name="pincode" value="{{ old('pincode', $address?->pincode) }}"
                               required inputmode="numeric" maxlength="6" autocomplete="postal-code" class="od-input">
                        @error('pincode')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="city" class="od-label">Town or city</label>
                        <input id="city" name="city" value="{{ old('city', $address?->city) }}"
                               required autocomplete="address-level2" class="od-input">
                        @error('city')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="state" class="od-label">State</label>
                        <input id="state" name="state" value="{{ old('state', $address?->state) }}"
                               required autocomplete="address-level1" class="od-input">
                        @error('state')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="note" class="od-label">Anything we should know <span class="normal-case text-ink-faint">(optional)</span></label>
                        <textarea id="note" name="note" rows="2" class="od-input">{{ old('note') }}</textarea>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="font-head text-xl">How you would like to pay</h2>

                @error('method')<p class="mt-2 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror

                <div class="mt-5 space-y-3">
                    @if ($online)
                        <label class="od-card flex items-start gap-3 p-4 cursor-pointer has-[:checked]:border-[color:var(--color-gold)]">
                            <input type="radio" name="method" value="razorpay" x-model="method"
                                   class="mt-1 accent-[color:var(--color-gold)]">
                            <span>
                                <span class="block font-medium">UPI, card, net banking or wallet</span>
                                <span class="block text-sm text-ink-muted mt-0.5">Paid securely through Razorpay. We never see your card.</span>
                            </span>
                        </label>
                    @endif

                    @if ($codOn)
                        <label class="od-card flex items-start gap-3 p-4 cursor-pointer has-[:checked]:border-[color:var(--color-gold)]">
                            <input type="radio" name="method" value="cod" x-model="method"
                                   class="mt-1 accent-[color:var(--color-gold)]">
                            <span>
                                <span class="block font-medium">Cash on delivery</span>
                                <span class="block text-sm text-ink-muted mt-0.5">
                                    Pay the courier when it arrives.
                                    @if (Shop::codFee() > 0)
                                        {{ Shop::money(Shop::codFee()) }} is added for this.
                                    @endif
                                </span>
                            </span>
                        </label>
                    @elseif ($codWhy)
                        {{-- Said plainly rather than simply left out, so nobody
                             hunts for an option that is not there. --}}
                        <p class="text-sm text-ink-muted px-1">{{ $codWhy }}</p>
                    @endif

                    @if (! $online && ! $codOn)
                        <p class="text-sm text-[color:var(--color-marigold)]">
                            No way to pay is set up yet. Please
                            <a href="{{ route('page', 'contact') }}" class="text-gold">get in touch</a>
                            and we will take the order ourselves.
                        </p>
                    @endif
                </div>
            </section>
        </div>

        <aside class="lg:sticky lg:top-28 h-fit space-y-5">
            <div class="od-card p-6">
                <h2 class="font-head text-xl">Your order</h2>

                <ul class="mt-5 space-y-4">
                    @foreach ($cart->items as $item)
                        @php $image = $item->product?->firstImage($item->colourway); @endphp
                        <li class="flex gap-3">
                            @if ($image)
                                <img src="{{ $image->url }}" alt="" class="w-14 h-[74px] object-cover rounded" width="56" height="74">
                            @endif
                            <div class="flex-1 min-w-0 text-sm">
                                <p class="leading-snug">{{ $item->product?->name }}</p>
                                <p class="text-ink-faint mt-0.5">
                                    @if ($item->colourway){{ $item->colourway->name }} · @endif× {{ $item->quantity }}
                                </p>
                            </div>
                            <p class="text-sm">{{ Shop::money(($item->product?->priceFor($item->colourway) ?? 0) * $item->quantity) }}</p>
                        </li>
                    @endforeach
                </ul>

                <div class="od-rule my-5"></div>

                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">Sarees</dt>
                        <dd>{{ Shop::money($totals->subtotal) }}</dd>
                    </div>
                    @if ($totals->offerTotal > 0)
                        <div class="flex justify-between text-gold-light">
                            <dt>Offers</dt><dd>−{{ Shop::money($totals->offerTotal) }}</dd>
                        </div>
                    @endif
                    @if ($totals->couponTotal > 0)
                        <div class="flex justify-between text-gold-light">
                            <dt>{{ $totals->couponCode }}</dt><dd>−{{ Shop::money($totals->couponTotal) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">Delivery</dt>
                        <dd>{{ $totals->shippingTotal > 0 ? Shop::money($totals->shippingTotal) : 'Free' }}</dd>
                    </div>
                    @if (Shop::codFee() > 0)
                        <div class="flex justify-between" x-show="method === 'cod'" x-cloak>
                            <dt class="text-ink-muted">Cash on delivery</dt>
                            <dd>{{ Shop::money(Shop::codFee()) }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="od-rule my-5"></div>

                <div class="flex items-baseline justify-between">
                    <span class="font-head text-lg">To pay</span>
                    <span class="font-head text-2xl"
                          x-text="method === 'cod' ? @js(Shop::money($totals->grandTotal + Shop::codFee())) : @js(Shop::money($totals->grandTotal))"
                    >{{ Shop::money($totals->grandTotal + (old('method', $online ? 'razorpay' : 'cod') === 'cod' ? Shop::codFee() : 0)) }}</span>
                </div>

                <p class="mt-2 text-xs text-ink-faint">
                    Delivery is worked out again from your pincode when you place the order.
                </p>

                <button type="submit" class="od-btn od-btn-gold w-full mt-6" @disabled(! $online && ! $codOn)>
                    Place the order
                </button>

                <a href="{{ route('bag') }}" class="od-btn od-btn-ghost w-full mt-3">Back to your bag</a>
            </div>
        </aside>
    </form>
</div>
@endsection
