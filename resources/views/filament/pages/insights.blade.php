@php
    $h = $this->headline();
    $google = $this->google();
    $about = $this->whoIsAbout();
    $discounts = $this->discounts();
    $empty = $this->emptySearches();
    $cold = $this->lookedAtNotBought();
@endphp

<x-filament-panels::page>
    {{--
        Plain CSS rather than Tailwind classes.

        Filament compiles its own stylesheet from its own views, so a utility
        class used only here is simply not in it — which is why this page first
        rendered as one unstyled column. Building a custom Filament theme would
        fix it and add a second asset pipeline to a shop deployed by uploading
        files to shared hosting. This does not.
    --}}
    <style>
        .od-periods { display: flex; flex-wrap: wrap; gap: .5rem; }
        .od-period {
            border-radius: 9999px; padding: .4rem 1rem; font-size: .8125rem;
            border: 1px solid rgb(228 228 231); background: #fff; color: rgb(82 82 91);
            cursor: pointer; transition: background .15s, color .15s;
        }
        .od-period:hover { background: rgb(244 244 245); }
        .od-period[aria-pressed="true"] { background: #8a6c50; border-color: #8a6c50; color: #fff; }

        .od-stats { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .od-two   { display: grid; gap: 1.5rem; grid-template-columns: 1fr; }
        @media (min-width: 1024px) {
            .od-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .od-two   { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .od-stat-label { font-size: .8125rem; color: rgb(113 113 122); }
        .od-stat-value { margin-top: .25rem; font-size: 1.5rem; font-weight: 600; letter-spacing: -.02em; }
        .od-stat-note  { margin-top: .25rem; font-size: .75rem; color: rgb(113 113 122); }

        .od-row {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: .5rem 0; border-bottom: 1px solid rgb(244 244 245); font-size: .875rem;
        }
        .od-row:last-child { border-bottom: 0; }
        .od-row a { color: inherit; }
        .od-row a:hover { color: #8a6c50; }
        .od-row-right { flex-shrink: 0; text-align: right; white-space: nowrap; }
        .od-muted { color: rgb(113 113 122); }
        .od-strong { font-weight: 500; }
        .od-warn { color: rgb(190 18 60); }
        .od-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .od-sub { font-size: .75rem; color: rgb(113 113 122); margin-top: .125rem; }
        .od-empty { font-size: .875rem; color: rgb(113 113 122); }
        .od-foot { font-size: .75rem; color: rgb(113 113 122); }

        .dark .od-period { background: transparent; border-color: rgb(63 63 70); color: rgb(212 212 216); }
        .dark .od-period:hover { background: rgb(39 39 42); }
        .dark .od-row { border-bottom-color: rgb(39 39 42); }
        .dark .od-stat-label, .dark .od-stat-note, .dark .od-muted, .dark .od-sub,
        .dark .od-empty, .dark .od-foot { color: rgb(161 161 170); }
    </style>

    <div class="od-periods">
        @foreach ($this->periods() as $days => $label)
            <button type="button" class="od-period"
                    aria-pressed="{{ $period === (string) $days ? 'true' : 'false' }}"
                    wire:click="$set('period', '{{ $days }}')">{{ $label }}</button>
        @endforeach
    </div>

    <div class="od-stats">
        @foreach ([
            ['Taken', $this->money($h['taken']), $h['orders'] . ' paid ' . \Illuminate\Support\Str::plural('order', $h['orders'])],
            ['Average order', $this->money($h['average']), $h['pieces'] . ' ' . \Illuminate\Support\Str::plural('piece', $h['pieces']) . ' sold'],
            ['Bags left behind', (string) $h['left'], $h['conversion'] !== null ? $h['conversion'] . '% of bags became orders' : 'Nobody has filled one yet'],
            ['Waiting on you', (string) \App\Models\Order::whereIn('status', ['pending', 'confirmed'])->count(), 'Across all time'],
        ] as [$label, $value, $note])
            <x-filament::section>
                <p class="od-stat-label">{{ $label }}</p>
                <p class="od-stat-value">{{ $value }}</p>
                <p class="od-stat-note">{{ $note }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <div class="od-two">
        <x-filament::section heading="What sold" description="Paid orders only, best first.">
            @forelse ($this->bestSellers() as $row)
                <div class="od-row">
                    <span>{{ $row->name }}</span>
                    <span class="od-row-right">
                        <span class="od-muted">{{ $row->pieces }} ×</span>
                        <span class="od-strong">&nbsp;{{ $this->money($row->money) }}</span>
                    </span>
                </div>
            @empty
                <p class="od-empty">Nothing sold in this stretch.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="Which shades went" description="What to ask the weaver for next.">
            @forelse ($this->bestShades() as $row)
                <div class="od-row">
                    <span>{{ $row->shade }}</span>
                    <span class="od-row-right od-muted">{{ $row->pieces }}</span>
                </div>
            @empty
                <p class="od-empty">No shades recorded yet.</p>
            @endforelse
        </x-filament::section>
    </div>

    <x-filament::section
        heading="Looked at, rarely bought"
        description="Almost always the photograph, the price, or a description that does not say what somebody needed to know. The most fixable problem a shop has.">
        @forelse ($cold as $row)
            <div class="od-row">
                <span>
                    <a href="{{ \App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $row['product']->id]) }}"
                       class="od-strong">{{ $row['product']->name }}</a>
                    <span class="od-sub" style="display:block">
                        {{ $this->money($row['product']->sale_price ?: $row['product']->price) }}@if ($row['product']->sku) · {{ $row['product']->sku }}@endif
                    </span>
                </span>
                <span class="od-row-right">
                    <span class="od-muted">{{ $row['views'] }} looks</span>
                    <span class="od-strong">&nbsp;→&nbsp;{{ $row['sold'] }} sold</span>
                    <span class="od-sub {{ $row['rate'] < 1 ? 'od-warn' : '' }}" style="display:block">{{ $row['rate'] }}%</span>
                </span>
            </div>
        @empty
            <p class="od-empty">Not enough has been looked at yet to say.</p>
        @endforelse
    </x-filament::section>

    <div class="od-two">
        <x-filament::section heading="What people searched for">
            @forelse ($this->searches() as $row)
                <div class="od-row">
                    <a href="{{ route('shop', ['q' => $row->normalised]) }}" target="_blank">{{ $row->normalised }}</a>
                    <span class="od-row-right">
                        <span class="od-muted">{{ $row->times }} ×</span>
                        @if ($row->worst == 0)
                            <span class="od-warn">&nbsp;found nothing</span>
                        @else
                            <span class="od-muted">&nbsp;{{ $row->best }} shown</span>
                        @endif
                    </span>
                </div>
            @empty
                <p class="od-empty">Nobody has used the search box yet.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="Asked for and not found"
                             description="Somebody telling you what to buy next, in their own words.">
            @forelse ($empty as $row)
                <div class="od-row">
                    <span>{{ $row->normalised }}</span>
                    <span class="od-row-right od-muted">
                        {{ $row->times }} × · {{ \Illuminate\Support\Carbon::parse($row->last_asked)->diffForHumans() }}
                    </span>
                </div>
            @empty
                <p class="od-empty">Every search found something. Good.</p>
            @endforelse
        </x-filament::section>
    </div>

    <div class="od-two">
        <x-filament::section heading="Where the parcels go">
            @forelse ($this->states() as $row)
                <div class="od-row">
                    <span>{{ $row['state'] }}</span>
                    <span class="od-row-right">
                        <span class="od-muted">{{ $row['orders'] }} ×</span>
                        <span class="od-strong">&nbsp;{{ $this->money($row['money']) }}</span>
                    </span>
                </div>
            @empty
                <p class="od-empty">No paid orders in this stretch.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="How they paid">
            @forelse ($this->payment() as $row)
                <div class="od-row">
                    <span>
                        {{ $row->payment_method === 'cod' ? 'Cash on delivery' : ucfirst((string) $row->payment_method ?: 'Not chosen') }}
                        <span class="od-sub">{{ $row->payment_status }}</span>
                    </span>
                    <span class="od-row-right">
                        <span class="od-muted">{{ $row->orders }} ×</span>
                        <span class="od-strong">&nbsp;{{ $this->money($row->money) }}</span>
                    </span>
                </div>
            @empty
                <p class="od-empty">No orders in this stretch.</p>
            @endforelse
        </x-filament::section>
    </div>

    <x-filament::section heading="What the offers did"
                         description="Whether giving something away actually brought orders in.">
        @if ($discounts['offers'] === [] && $discounts['coupons'] === [])
            <p class="od-empty">No offer or code was used in this stretch.</p>
        @else
            @foreach ($discounts['offers'] as $row)
                <div class="od-row">
                    <span>{{ $row->name }}</span>
                    <span class="od-row-right od-muted">
                        {{ $row->orders }} {{ \Illuminate\Support\Str::plural('order', $row->orders) }}@if ($row->given > 0) · {{ $row->given }} given away @endif
                    </span>
                </div>
            @endforeach

            @foreach ($discounts['coupons'] as $row)
                <div class="od-row">
                    <span class="od-mono">{{ $row->name }}</span>
                    <span class="od-row-right od-muted">
                        {{ $row->orders }} {{ \Illuminate\Support\Str::plural('order', $row->orders) }} · {{ $this->money($row->given_off) }} off
                    </span>
                </div>
            @endforeach
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Who is about</x-slot>
        <x-slot name="description">
            The ones the shop can name, from its own tables. Google counts everybody; this counts
            the people with an account.
        </x-slot>

        <div class="od-stats">
            <div>
                <div class="od-stat-label">On the site now</div>
                <div class="od-stat-value">
                    {{ $about['live'] === null ? '—' : number_format($about['live']) }}
                </div>
                <div class="od-stat-note">
                    @if ($about['live'] === null)
                        Needs SESSION_DRIVER=database
                    @else
                        In the last five minutes
                    @endif
                </div>
            </div>
            <div>
                <div class="od-stat-label">Signed in now</div>
                <div class="od-stat-value">
                    {{ $about['liveNamed'] === null ? '—' : number_format($about['liveNamed']) }}
                </div>
                <div class="od-stat-note">
                    @if ($about['live'] !== null && $about['live'] > 0)
                        {{ number_format($about['liveNamed'] / $about['live'] * 100) }}% of those here
                    @else
                        Of the people here
                    @endif
                </div>
            </div>
            <div>
                <div class="od-stat-label">Signed in at all</div>
                <div class="od-stat-value">{{ number_format($about['signedIn']) }}</div>
                <div class="od-stat-note">In this stretch</div>
            </div>
            <div>
                <div class="od-stat-label">New accounts</div>
                <div class="od-stat-value">{{ number_format($about['joined']) }}</div>
                <div class="od-stat-note">{{ number_format($about['accounts']) }} in all</div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">What Google knows</x-slot>
        <x-slot name="description">
            Who arrived, and what was typed in to find the shop — over the same stretch as everything above.
        </x-slot>

        @if (! $google['set'])
            <p class="od-empty">
                Not set up yet. Settings → Analytics → <strong>Reading the figures back</strong> takes three
                things: the key file Google gives a service account, the Analytics property number, and the
                Search Console property. The shop works perfectly well without them; this panel is the only
                thing that goes without.
            </p>
        @else
            @if ($google['visitors'])
                <div class="od-stats">
                    <div>
                        <div class="od-stat-label">People</div>
                        <div class="od-stat-value">{{ number_format($google['visitors']['people']) }}</div>
                        <div class="od-stat-note">Last {{ $google['days'] }} days</div>
                    </div>
                    <div>
                        <div class="od-stat-label">Visits</div>
                        <div class="od-stat-value">{{ number_format($google['visitors']['visits']) }}</div>
                    </div>
                    <div>
                        <div class="od-stat-label">Pages looked at</div>
                        <div class="od-stat-value">{{ number_format($google['visitors']['pages']) }}</div>
                    </div>
                    <div>
                        <div class="od-stat-label">Orders from them</div>
                        <div class="od-stat-value">{{ number_format($h['orders']) }}</div>
                        <div class="od-stat-note">
                            @if ($google['visitors']['visits'] > 0)
                                {{ number_format($h['orders'] / $google['visitors']['visits'] * 100, 2) }}% of visits
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if ($google['visitors']['from'])
                    <div style="margin-top: 1.25rem">
                        <div class="od-stat-label" style="margin-bottom:.25rem">Where they came from</div>
                        @foreach ($google['visitors']['from'] as $row)
                            <div class="od-row">
                                <span>{{ $row['name'] }}</span>
                                <span class="od-row-right od-muted">{{ number_format($row['visits']) }} visits</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="od-empty">
                    No visitor figures. Either the Analytics property number is wrong, or that service account
                    has not been given Viewer on the property — Analytics → Admin → Property access management.
                </p>
            @endif

            @if ($google['search'])
                <div style="margin-top: 1.5rem">
                    <div class="od-stats">
                        <div>
                            <div class="od-stat-label">Clicks from Google</div>
                            <div class="od-stat-value">{{ number_format($google['search']['clicks']) }}</div>
                        </div>
                        <div>
                            <div class="od-stat-label">Times shown</div>
                            <div class="od-stat-value">{{ number_format($google['search']['impressions']) }}</div>
                        </div>
                        <div>
                            <div class="od-stat-label">Clicked</div>
                            <div class="od-stat-value">{{ number_format($google['search']['ctr'], 1) }}%</div>
                            <div class="od-stat-note">Of the times it was shown</div>
                        </div>
                        <div>
                            <div class="od-stat-label">Average position</div>
                            <div class="od-stat-value">{{ number_format($google['search']['position'], 1) }}</div>
                            <div class="od-stat-note">1 is the top of the first page</div>
                        </div>
                    </div>

                    @if ($google['search']['queries'])
                        <div style="margin-top: 1.25rem">
                            <div class="od-stat-label" style="margin-bottom:.25rem">What they typed</div>
                            @foreach ($google['search']['queries'] as $row)
                                <div class="od-row">
                                    <span>{{ $row['words'] }}</span>
                                    <span class="od-row-right od-muted">
                                        {{ number_format($row['clicks']) }} {{ \Illuminate\Support\Str::plural('click', $row['clicks']) }}
                                        · shown {{ number_format($row['impressions']) }} · position {{ $row['position'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <p class="od-foot" style="margin-top:1rem">
                        Google's own search figures run about three days behind, so this stops three days ago.
                    </p>
                </div>
            @else
                <p class="od-empty" style="margin-top:1rem">
                    No search figures. Either the Search Console property is spelled differently there —
                    it wants <span class="od-mono">sc-domain:ojasvidrapes.in</span> or
                    <span class="od-mono">https://ojasvidrapes.in/</span>, exactly as Search Console has it —
                    or that service account has not been added as a user of it.
                </p>
            @endif
        @endif
    </x-filament::section>

    <p class="od-foot">
        Every money figure counts paid orders only. A bag abandoned at the payment page is not a sale,
        and a page that counted it would be believed until the bank statement disagreed.
    </p>
</x-filament-panels::page>
