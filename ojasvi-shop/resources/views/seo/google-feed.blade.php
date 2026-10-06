<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
    <title>{{ \App\Support\Shop::name() }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ \App\Support\Seo::homeDescription() }}</description>
@foreach ($items as $item)
    <item>
        <g:id>{{ $item['id'] }}</g:id>
        <g:item_group_id>{{ $item['item_group'] }}</g:item_group_id>
        <title><![CDATA[{{ $item['title'] }}]]></title>
        <description><![CDATA[{{ $item['description'] }}]]></description>
        <link>{{ $item['link'] }}</link>
@if ($item['image'])
        <g:image_link>{{ $item['image'] }}</g:image_link>
@foreach ($item['extra'] as $extra)
        <g:additional_image_link>{{ $extra }}</g:additional_image_link>
@endforeach
@endif
        <g:availability>{{ $item['availability'] }}</g:availability>
        <g:price>{{ $item['price'] }} INR</g:price>
@if ($item['sale'])
        <g:sale_price>{{ $item['sale'] }} INR</g:sale_price>
@endif
        <g:brand><![CDATA[{{ $item['brand'] }}]]></g:brand>
        <g:condition>new</g:condition>
        <g:google_product_category>{{ $item['category'] }}</g:google_product_category>
        <g:product_type><![CDATA[{{ $item['type'] }}]]></g:product_type>
@if ($item['colour'])
        <g:color><![CDATA[{{ $item['colour'] }}]]></g:color>
@endif
@if ($item['weight'])
        <g:shipping_weight>{{ $item['weight'] }} g</g:shipping_weight>
@endif
        <g:identifier_exists>no</g:identifier_exists>
    </item>
@endforeach
</channel>
</rss>
