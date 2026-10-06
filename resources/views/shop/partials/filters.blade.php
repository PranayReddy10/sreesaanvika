@php use App\Support\Shop; @endphp

<form method="get" action="{{ route('shop') }}" class="space-y-7">
    @if ($q)
        <input type="hidden" name="q" value="{{ $q }}">
    @endif
    @if ($sort)
        <input type="hidden" name="sort" value="{{ $sort }}">
    @endif

    @if ($chosen || $onOffer || request('min') || request('max'))
        <a href="{{ route('shop', array_filter(['q' => $q, 'sort' => $sort])) }}"
           class="inline-block text-sm text-gold hover:text-gold-light transition">Clear everything</a>
    @endif

    <div>
        <h3 class="od-eyebrow mb-3">Price</h3>
        <div class="flex items-center gap-2">
            <input type="number" name="min" value="{{ request('min') }}" min="0"
                   placeholder="{{ (int) $bounds['min'] }}" class="od-input py-2 text-sm" aria-label="Lowest price">
            <span class="text-ink-faint">–</span>
            <input type="number" name="max" value="{{ request('max') }}" min="0"
                   placeholder="{{ (int) $bounds['max'] }}" class="od-input py-2 text-sm" aria-label="Highest price">
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" name="on" value="offer" @checked($onOffer)
                   class="w-4 h-4 accent-[color:var(--color-gold)]">
            <span class="text-sm">On offer only</span>
        </label>
    </div>

    @foreach ($filters as $attribute)
        @continue($attribute->values->isEmpty())
        @php $picked = $chosen[$attribute->slug] ?? []; @endphp

        <div>
            <h3 class="od-eyebrow mb-3">{{ $attribute->name }}</h3>
            <div class="space-y-2 max-h-56 overflow-y-auto od-scroll pr-1">
                @foreach ($attribute->values as $value)
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <input type="checkbox"
                               name="{{ $attribute->slug }}[]"
                               value="{{ $value->slug }}"
                               @checked(in_array($value->slug, $picked, true))
                               class="w-4 h-4 accent-[color:var(--color-gold)]">
                        <span class="text-sm text-ink-soft group-hover:text-ink transition">{{ $value->value }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach

    <button type="submit" class="od-btn od-btn-gold w-full">Show these sarees</button>
</form>
