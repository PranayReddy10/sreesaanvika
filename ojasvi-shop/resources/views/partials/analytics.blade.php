@php
    use App\Support\Seo;

    $ga4 = Seo::ga4();
    $pixel = Seo::metaPixel();
    $ads = Seo::googleAds();
@endphp

@if ($ga4 || $ads)
    {{-- One gtag.js for both Google Analytics and Google Ads; loading it twice
         is the usual way a shop ends up counting every sale twice. --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 ?: $ads }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if ($ga4)
            gtag('config', @json($ga4));
        @endif
        @if ($ads)
            gtag('config', @json($ads));
        @endif
    </script>
@endif

@if ($pixel)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($pixel));
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" alt=""
             src="https://www.facebook.com/tr?id={{ $pixel }}&ev=PageView&noscript=1">
    </noscript>
@endif

@if (Seo::anyAnalytics())
    {{--
        One place that reports a shopping event to whichever of the three are
        set up, so the rest of the shop says `odTrack('add_to_cart', {...})`
        once and does not have to know which services exist. Google and Meta
        want the same facts in different shapes; the translation lives here.
    --}}
    <script>
        window.odTrack = function (event, data) {
            data = data || {};

            try {
                if (typeof gtag === 'function') {
                    gtag('event', event, {
                        currency: 'INR',
                        value: data.value,
                        items: data.items,
                        transaction_id: data.order,
                        coupon: data.coupon,
                        shipping: data.shipping,
                    });
                }

                if (typeof fbq === 'function') {
                    var meta = {
                        view_item: 'ViewContent',
                        add_to_cart: 'AddToCart',
                        begin_checkout: 'InitiateCheckout',
                        purchase: 'Purchase',
                        search: 'Search',
                        add_to_wishlist: 'AddToWishlist',
                    }[event];

                    if (meta) {
                        fbq('track', meta, {
                            currency: 'INR',
                            value: data.value,
                            content_type: 'product',
                            content_ids: (data.items || []).map(function (i) { return i.item_id; }),
                            contents: (data.items || []).map(function (i) {
                                return { id: i.item_id, quantity: i.quantity, item_price: i.price };
                            }),
                            search_string: data.search,
                        });
                    }
                }

                @if ($ads && Seo::googleAdsPurchaseLabel())
                    if (event === 'purchase' && typeof gtag === 'function') {
                        gtag('event', 'conversion', {
                            send_to: @json($ads . '/' . Seo::googleAdsPurchaseLabel()),
                            value: data.value,
                            currency: 'INR',
                            transaction_id: data.order,
                        });
                    }
                @endif
            } catch (e) {
                // Analytics must never be the reason a shopper cannot buy.
            }
        };
    </script>
@else
    <script>window.odTrack = function () {};</script>
@endif
