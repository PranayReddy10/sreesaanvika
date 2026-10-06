<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Colourway;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\Section;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\Slide;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A shop with something in it.
 *
 * This is the demonstration catalogue: sixteen designs, each in two shades,
 * with the offers, orders and reviews a real week would leave behind. It
 * exists so the admin can be judged against real rows rather than empty
 * tables, and so the storefront can be opened the day it is deployed.
 *
 * Run it on a fresh database only — it writes, it does not reconcile.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->images();
        $this->settings();
        $this->shipping();
        $attributes = $this->attributes();
        $collections = $this->collections();
        $products = $this->sarees($attributes, $collections);
        $this->videos($products);
        $this->offers($products, $collections);
        $this->coupons();
        $this->content();
        $customers = $this->customers();
        $this->orders($products, $customers);
        $this->reviews($products, $customers);
    }

    /**
     * The example photographs ship with the seeder rather than with the shop,
     * because uploads are not in version control. Copied, never moved, so the
     * seeder can be run again.
     */
    private function images(): void
    {
        $from = database_path('seeders/demo-images');

        if (! is_dir($from)) {
            $this->command?->warn('No demo images found; sarees will be seeded without photographs.');

            return;
        }

        $disk = Storage::disk('public');

        foreach (glob($from . '/*.jpg') ?: [] as $file) {
            $target = 'products/' . basename($file);

            if (! $disk->exists($target)) {
                $disk->put($target, (string) file_get_contents($file));
            }
        }
    }

    /**
     * The example films, copied out of the seeder the same way the
     * photographs are. Short, portrait and small, which is the shape a real
     * one should be too.
     */
    private function videos(array $products): void
    {
        $from = database_path('seeders/demo-videos');

        if (! is_dir($from)) {
            return;
        }

        $disk = Storage::disk('public');

        // slug => [which saree it is of, what to call it, a line under it]
        $plan = [
            'kanjivaram-indigo' => [0, 'Draped three ways', 'Pure silk, pure zari'],
            'banarasi-gold'     => [1, 'The pallu, close up', 'Woven on a handloom in Varanasi'],
            'mysore-emerald'    => [2, 'How a soft silk falls', 'Light enough for a whole day'],
            'linen-blush'       => [6, 'Worn to work', 'Linen softens after the first wash'],
        ];

        $position = 0;

        foreach ($plan as $slug => [$index, $title, $caption]) {
            $file = "{$from}/{$slug}.webm";

            if (! is_file($file)) {
                continue;
            }

            $target = "videos/{$slug}.webm";

            if (! $disk->exists($target)) {
                $disk->put($target, (string) file_get_contents($file));
            }

            $product = $products[$index] ?? null;

            Video::create([
                'product_id' => $product?->id,
                'on_home'    => true,
                'title'      => $title,
                'caption'    => $caption,
                'path'       => $target,
                'position'   => $position++,
                'is_visible' => true,
            ]);
        }
    }

    private function settings(): void
    {
        $rows = [
            ['general', 'shop_name', 'OJASVI', 'string'],
            ['general', 'tagline', 'Handwoven sarees, chosen one at a time', 'string'],
            ['general', 'email', 'care@ojasvidrapes.in', 'string'],
            ['general', 'phone', '+91 90000 00000', 'string'],
            ['general', 'whatsapp', '919000000000', 'string'],
            ['general', 'address', "Hyderabad, Telangana\nIndia", 'text'],
            ['shipping', 'free_shipping_from', '2999', 'money'],
            ['shipping', 'flat_rate', '99', 'money'],
            ['shipping', 'cod_fee', '49', 'money'],
            ['shipping', 'dispatch_days', '2', 'int'],
            ['announcement', 'bar_text', 'Free shipping on orders over ₹2,999', 'string'],
            ['announcement', 'bar_url', '/sarees', 'string'],
            ['announcement', 'bar_on', '1', 'bool'],
            ['social', 'instagram', 'https://instagram.com/ojasvidrapes', 'string'],
            ['social', 'facebook', '', 'string'],
            // The policy pages are left empty on purpose: the shop stands
            // behind App\Support\Policies until somebody writes its own, and a
            // one-line stub here would be worse than the wording it replaces.
        ];

        foreach ($rows as [$group, $key, $value, $type]) {
            Setting::updateOrCreate(['key' => $key], compact('group', 'value', 'type'));
        }
    }

    private function shipping(): void
    {
        ShippingZone::create([
            'name' => 'Telangana & Andhra Pradesh',
            'pincodes' => ['50', '51', '52', '53'],
            'rate' => 49, 'free_from' => 1999,
            'days_min' => 1, 'days_max' => 3,
            'cod_allowed' => true, 'position' => 1,
        ]);

        ShippingZone::create([
            'name' => 'North East & islands',
            'pincodes' => ['78', '79', '74', '68'],
            'rate' => 149, 'free_from' => null,
            'days_min' => 6, 'days_max' => 12,
            'cod_allowed' => false, 'position' => 2,
        ]);

        // Last on purpose: a zone with no prefixes matches everything, so the
        // catch-all placed before a specific zone would swallow it.
        ShippingZone::create([
            'name' => 'Rest of India',
            'pincodes' => [],
            'rate' => 99, 'free_from' => 2999,
            'days_min' => 3, 'days_max' => 7,
            'cod_allowed' => true, 'position' => 3,
        ]);
    }

    /** @return array<string, array<string, AttributeValue>> */
    private function attributes(): array
    {
        $plan = [
            'Fabric' => ['Pure silk', 'Soft silk', 'Cotton silk', 'Linen', 'Organza', 'Tussar silk'],
            'Weave'  => ['Kanjivaram', 'Banarasi', 'Mysore', 'Chanderi', 'Paithani', 'Patola', 'Jamdani', 'Bandhani', 'Kalamkari'],
            'Occasion' => ['Wedding', 'Festive', 'Everyday', 'Office', 'Reception'],
            'Shade'  => ['Indigo', 'Maroon', 'Gold', 'Ivory', 'Emerald', 'Rose', 'Grey', 'White', 'Ochre', 'Teal', 'Blush', 'Lilac', 'Crimson', 'Mint', 'Saffron', 'Sand'],
        ];

        $out = [];
        $position = 0;

        foreach ($plan as $name => $values) {
            $attribute = Attribute::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'is_filterable' => true,
                'position' => ++$position,
            ]);

            foreach ($values as $i => $value) {
                $out[$name][$value] = AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                    'slug' => Str::slug($value),
                    'position' => $i,
                ]);
            }
        }

        return $out;
    }

    /**
     * Collections, not categories.
     *
     * The storefront has no category menu — the shop sells sarees and nothing
     * else, so a menu of one thing is noise. These rows exist so an offer can
     * be pointed at a group of designs in one go.
     *
     * @return array<string, Category>
     */
    private function collections(): array
    {
        $out = [];
        $plan = [
            'Bridal' => 'The heavy silks: Kanjivaram, Banarasi, Patola.',
            'Everyday' => 'Light enough to wear to work and out after.',
            'Festive' => 'What gets worn at Sankranti, Diwali and the weddings between.',
        ];

        $position = 0;
        foreach ($plan as $name => $description) {
            $out[$name] = Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'position' => ++$position,
                'is_visible' => false,   // grouping for offers, not a shop menu
            ]);
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, AttributeValue>>  $attributes
     * @param  array<string, Category>  $collections
     * @return array<int, Product>
     */
    private function sarees(array $attributes, array $collections): array
    {
        // name, price, sale, stock, fabric, weave, occasion, collection, featured,
        // [shade => [hex, image slug]]
        $plan = [
            ['Kanjivaram in deep indigo', 18500, 15900, 4, 'Pure silk', 'Kanjivaram', 'Wedding', 'Bridal', true, [
                'Indigo' => ['#2f3a6e', 'kanjivaram-indigo'],
                'Maroon' => ['#6e1f2b', 'kanjivaram-maroon'],
            ]],
            ['Banarasi with a gold pallu', 22400, null, 3, 'Pure silk', 'Banarasi', 'Wedding', 'Bridal', true, [
                'Antique gold' => ['#a8781f', 'banarasi-gold'],
                'Ivory' => ['#ece3d2', 'banarasi-ivory'],
            ]],
            ['Mysore silk, plain field', 9800, 8900, 7, 'Soft silk', 'Mysore', 'Festive', 'Festive', true, [
                'Emerald' => ['#1f5a46', 'mysore-emerald'],
                'Rose' => ['#b4546a', 'mysore-rose'],
            ]],
            ['Chanderi with a fine zari line', 6400, null, 9, 'Cotton silk', 'Chanderi', 'Office', 'Everyday', false, [
                'Pewter grey' => ['#5d5f63', 'chanderi-grey'],
                'Chalk white' => ['#f3f0e9', 'chanderi-white'],
            ]],
            ['Tussar in warm ochre', 7200, 6500, 5, 'Tussar silk', 'Mysore', 'Festive', 'Festive', false, [
                'Ochre' => ['#b9842f', 'tussar-ochre'],
            ]],
            ['Paithani with a peacock border', 16800, null, 2, 'Pure silk', 'Paithani', 'Reception', 'Bridal', true, [
                'Peacock teal' => ['#1d5f69', 'paithani-teal'],
            ]],
            ['Linen in soft blush', 4900, 4200, 12, 'Linen', 'Chanderi', 'Everyday', 'Everyday', false, [
                'Blush' => ['#dfb2ad', 'linen-blush'],
            ]],
            ['Organza with scattered buttis', 5600, null, 8, 'Organza', 'Banarasi', 'Festive', 'Festive', false, [
                'Lilac' => ['#8f7aa8', 'organza-lilac'],
            ]],
            ['Patola double ikat', 26500, 23900, 1, 'Pure silk', 'Patola', 'Wedding', 'Bridal', true, [
                'Crimson' => ['#8c1f2f', 'patola-crimson'],
            ]],
            ['Jamdani in cool mint', 8100, null, 6, 'Cotton silk', 'Jamdani', 'Everyday', 'Everyday', false, [
                'Mint' => ['#9ec4ad', 'jamdani-mint'],
            ]],
            ['Bandhani in saffron', 6900, 5900, 10, 'Cotton silk', 'Bandhani', 'Festive', 'Festive', false, [
                'Saffron' => ['#d08020', 'bandhani-saffron'],
            ]],
            ['Kalamkari on sand', 7600, null, 4, 'Cotton silk', 'Kalamkari', 'Everyday', 'Everyday', false, [
                'Sand' => ['#c9b391', 'kalamkari-sand'],
            ]],
        ];

        $out = [];
        $n = 1200;

        foreach ($plan as [$name, $price, $sale, $stock, $fabric, $weave, $occasion, $collection, $featured, $shades]) {
            $product = Product::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'sku' => 'OD-' . $n++,
                'short_description' => "Handwoven {$fabric}, {$weave} tradition. Comes with an unstitched blouse piece.",
                'description' => "<p>A {$weave} saree in {$fabric}, woven on a handloom and finished by hand. "
                    . "The blouse piece is unstitched and matches the body.</p>"
                    . "<p>Six and a quarter metres including the blouse piece. Dry clean only; "
                    . "store folded in muslin and refold along a different line every few months.</p>",
                'price' => $price,
                'sale_price' => $sale,
                'sale_ends_at' => $sale ? now()->addDays(21) : null,
                'cost_price' => round($price * 0.62),
                'track_stock' => true,
                'stock' => $stock,
                'low_stock_at' => 3,
                'weight_g' => 700,
                'length_cm' => 35, 'width_cm' => 28, 'height_cm' => 8,
                'status' => 'published',
                'is_featured' => $featured,
                'published_at' => now()->subDays(random_int(1, 60)),
                'meta_title' => "{$name} — OJASVI",
                'meta_description' => "Handwoven {$weave} saree in {$fabric}. Free shipping over ₹2,999.",
                'views' => random_int(40, 900),
            ]);

            $ids = array_filter([
                $attributes['Fabric'][$fabric]->id ?? null,
                $attributes['Weave'][$weave]->id ?? null,
                $attributes['Occasion'][$occasion]->id ?? null,
            ]);
            $product->attributeValues()->sync($ids);
            $product->categories()->sync([$collections[$collection]->id]);

            $position = 0;
            foreach ($shades as $shade => [$hex, $slug]) {
                $colourway = Colourway::create([
                    'product_id' => $product->id,
                    'name' => $shade,
                    'hex' => $hex,
                    'sku' => $product->sku . '-' . strtoupper(substr(Str::slug($shade), 0, 3)),
                    'stock' => max(1, intdiv($stock, count($shades))),
                    'position' => $position,
                    'is_visible' => true,
                ]);

                foreach ([1, 2] as $i) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'colourway_id' => $colourway->id,
                        'path' => "products/{$slug}-{$i}.jpg",
                        'alt' => "{$name} in {$shade}",
                        'position' => $i - 1,
                    ]);
                }

                // The first shade's photographs double as the product's own, so
                // a listing has something to show before a shade is chosen.
                if ($position === 0) {
                    foreach ([1, 2] as $i) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'colourway_id' => null,
                            'path' => "products/{$slug}-{$i}.jpg",
                            'alt' => $name,
                            'position' => $i - 1,
                        ]);
                    }
                }

                $position++;
            }

            $out[] = $product;
        }

        // Complete the look: the two bridal silks suggest each other's shade.
        $out[0]->matches()->sync([$out[1]->id, $out[8]->id]);
        $out[1]->matches()->sync([$out[0]->id, $out[5]->id]);

        return $out;
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<string, Category>  $collections
     */
    private function offers(array $products, array $collections): void
    {
        $bogo = Offer::create([
            'name' => 'Buy 2 get 1 free — everyday sarees',
            'headline' => 'Buy 2, get 1 free',
            'kind' => 'bogo',
            'buy' => 2, 'get' => 1, 'percent' => 100,
            'repeats' => true,
            'applies_to_all' => false,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
            'position' => 1,
        ]);
        $bogo->categories()->sync([$collections['Everyday']->id]);

        $tiers = Offer::create([
            'name' => 'Festive quantity break',
            'headline' => 'Save more as you add more',
            'kind' => 'tiers',
            'tiers' => [
                ['qty' => 2, 'percent' => 5],
                ['qty' => 3, 'percent' => 10],
                ['qty' => 5, 'percent' => 15],
            ],
            'applies_to_all' => true,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addWeeks(6),
            'is_active' => true,
            'position' => 2,
        ]);

        $half = Offer::create([
            'name' => 'Third one half price — bridal',
            'headline' => 'Buy 2, third at half price',
            'kind' => 'bogo',
            'buy' => 2, 'get' => 1, 'percent' => 50,
            'repeats' => false,
            'applies_to_all' => false,
            'is_active' => false,          // written, not running
            'position' => 3,
        ]);
        $half->products()->sync([$products[0]->id, $products[1]->id, $products[8]->id]);
    }

    private function coupons(): void
    {
        Coupon::create([
            'code' => 'OJASVI10', 'type' => 'percent', 'value' => 10,
            'min_spend' => 4999, 'max_discount' => 2000,
            'usage_limit' => 500, 'usage_limit_per_user' => 1, 'used' => 37,
            'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(2),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FIRSTDRAPE', 'type' => 'fixed', 'value' => 500,
            'min_spend' => 3999, 'usage_limit_per_user' => 1, 'used' => 12,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SHIPFREE', 'type' => 'free_shipping', 'value' => 0,
            'min_spend' => 1499, 'used' => 64,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SANKRANTI25', 'type' => 'percent', 'value' => 25,
            'min_spend' => 9999, 'max_discount' => 5000,
            'usage_limit' => 100, 'used' => 100,
            'ends_at' => now()->subWeek(),
            'is_active' => false,          // spent and expired, kept for the record
        ]);
    }

    private function content(): void
    {
        $hero = Section::create([
            'key' => 'hero',
            'eyebrow' => 'Handwoven',
            'heading' => 'Sarees chosen one at a time',
            'position' => 1, 'is_visible' => true,
        ]);

        $slides = [
            ['Bridal', 'Kanjivaram, as it is woven', 'Pure silk, pure zari, and a border that takes a week.', 'See the bridal silks', '/sarees?collection=bridal', 'products/kanjivaram-indigo-1.jpg', 'left'],
            ['New this week', 'Banarasi with a gold pallu', 'Twelve designs, each in one or two shades. When they go, they go.', 'Shop new arrivals', '/sarees?sort=new', 'products/banarasi-gold-1.jpg', 'centre'],
            ['Everyday', 'Light enough for a working day', 'Chanderi, linen and cotton silk — buy two, the third is free.', 'Shop everyday sarees', '/sarees?collection=everyday', 'products/chanderi-grey-1.jpg', 'right'],
        ];

        foreach ($slides as $i => [$eyebrow, $heading, $text, $label, $url, $image, $align]) {
            Slide::create([
                'section_id' => $hero->id,
                'eyebrow' => $eyebrow, 'heading' => $heading, 'text' => $text,
                'button_label' => $label, 'button_url' => $url,
                'image' => $image, 'align' => $align,
                'position' => $i, 'is_visible' => true,
            ]);
        }

        Section::create([
            'key' => 'featured',
            'eyebrow' => 'The pick',
            'heading' => 'Five we would wear ourselves',
            'subheading' => 'Chosen for the weave, not the price.',
            'settings' => ['limit' => 5],
            'position' => 2, 'is_visible' => true,
        ]);

        Section::create([
            'key' => 'collection',
            'heading' => 'Every saree we have',
            'subheading' => 'Twelve designs. Tap one to see it in every shade.',
            'settings' => ['limit' => 12],
            'position' => 3, 'is_visible' => true,
        ]);

        Section::create([
            'key' => 'reels',
            'eyebrow' => 'In motion',
            'heading' => 'Seen worn',
            'subheading' => 'A photograph cannot show how a silk falls. These can.',
            'settings' => ['limit' => 6],
            'position' => 4, 'is_visible' => true,
        ]);

        Section::create([
            'key' => 'band',
            'eyebrow' => 'Why OJASVI',
            'heading' => 'Woven, checked, folded, sent',
            'subheading' => 'Every piece is photographed in daylight, checked for a pulled thread, and posted within two working days.',
            'position' => 5, 'is_visible' => true,
        ]);
    }

    /** @return array<int, User> */
    private function customers(): array
    {
        $plan = [
            ['Lakshmi Prasad', 'lakshmi@example.in', '9000000001', 'Hyderabad', 'Telangana', '500081'],
            ['Anjali Rao', 'anjali@example.in', '9000000002', 'Bengaluru', 'Karnataka', '560034'],
            ['Meera Iyer', 'meera@example.in', '9000000003', 'Chennai', 'Tamil Nadu', '600020'],
            ['Sunitha Reddy', 'sunitha@example.in', '9000000004', 'Warangal', 'Telangana', '506002'],
        ];

        $out = [];
        foreach ($plan as [$name, $email, $phone, $city, $state, $pincode]) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => 'password',   // the model's cast hashes it
                'email_verified_at' => now()->subMonths(2),
                'last_login_at' => now()->subDays(random_int(0, 20)),
            ]);

            $user->addresses()->create([
                'label' => 'Home',
                'name' => $name,
                'phone' => $phone,
                'line1' => random_int(1, 99) . '-' . random_int(1, 40) . ', Sai Nagar',
                'line2' => 'Near the temple',
                'city' => $city, 'state' => $state, 'pincode' => $pincode,
                'is_default' => true,
            ]);

            $out[] = $user;
        }

        return $out;
    }

    /**
     * Orders in every state the shop actually sees, so the list, the filters
     * and the "move on" action all have something to act on.
     *
     * @param  array<int, Product>  $products
     * @param  array<int, User>  $customers
     */
    private function orders(array $products, array $customers): void
    {
        $plan = [
            // status, payment_status, method, days ago, [product index => qty]
            ['pending',   'unpaid', 'cod',      0, [3 => 1]],
            ['confirmed', 'paid',   'razorpay', 1, [0 => 1, 2 => 1]],
            ['packed',    'paid',   'razorpay', 2, [6 => 2, 9 => 1]],
            ['shipped',   'paid',   'razorpay', 5, [1 => 1]],
            ['delivered', 'paid',   'razorpay', 14, [4 => 1, 10 => 1]],
            ['delivered', 'paid',   'cod',      21, [8 => 1]],
            ['cancelled', 'failed', 'razorpay', 9, [5 => 1]],
            ['refunded',  'refunded', 'razorpay', 30, [7 => 1]],
        ];

        $n = 41;

        foreach ($plan as [$status, $paymentStatus, $method, $daysAgo, $lines]) {
            $customer = $customers[array_rand($customers)];
            $address = $customer->addresses()->first()->only([
                'name', 'phone', 'line1', 'line2', 'city', 'state', 'pincode', 'country',
            ]);

            $placed = now()->subDays($daysAgo);
            $items = [];
            $itemsTotal = 0;

            foreach ($lines as $index => $quantity) {
                $product = $products[$index];
                $unit = (float) ($product->sale_price ?? $product->price);
                $colourway = $product->colourways()->first();

                $items[] = [
                    'product_id' => $product->id,
                    'colourway_id' => $colourway?->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'colourway_name' => $colourway?->name,
                    'image' => $product->images()->first()?->path,
                    'unit_price' => $unit,
                    'quantity' => $quantity,
                    'line_total' => $unit * $quantity,
                ];

                $itemsTotal += $unit * $quantity;
            }

            $shipping = $itemsTotal >= 2999 ? 0 : 99;
            $grand = $itemsTotal + $shipping;

            $order = Order::create([
                'number' => 'OJ-2026-' . str_pad((string) $n++, 5, '0', STR_PAD_LEFT),
                'user_id' => $customer->id,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => $method,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'items_total' => $itemsTotal,
                'shipping_total' => $shipping,
                'grand_total' => $grand,
                'refunded_total' => $paymentStatus === 'refunded' ? $grand : 0,
                'shipping_address' => $address,
                'note' => $status === 'pending' ? 'Please pack with the blouse piece separately.' : null,
                'placed_at' => $placed,
                'paid_at' => $paymentStatus === 'paid' ? $placed->copy()->addMinutes(2) : null,
                'cancelled_at' => $status === 'cancelled' ? $placed->copy()->addHours(4) : null,
                'created_at' => $placed,
                'updated_at' => $placed,
            ]);

            foreach ($items as $item) {
                OrderItem::create($item + ['order_id' => $order->id]);
            }

            if ($method === 'razorpay') {
                Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'razorpay',
                    'gateway_order_id' => 'order_' . Str::random(14),
                    'gateway_payment_id' => $paymentStatus === 'unpaid' ? null : 'pay_' . Str::random(14),
                    'method' => ['upi', 'card', 'netbanking'][array_rand(['upi', 'card', 'netbanking'])],
                    'amount' => $grand,
                    'status' => match ($paymentStatus) {
                        'paid' => 'captured',
                        'failed' => 'failed',
                        'refunded' => 'refunded',
                        default => 'created',
                    },
                    'failure_reason' => $paymentStatus === 'failed' ? 'Payment was not completed by the customer.' : null,
                    'created_at' => $placed,
                ]);
            }

            $order->history()->create(['to' => 'pending', 'note' => 'Placed', 'created_at' => $placed]);

            if (in_array($status, ['shipped', 'delivered'], true)) {
                $order->shipment()->create([
                    'courier' => 'delhivery',
                    'awb' => (string) random_int(10000000000, 99999999999),
                    'status' => $status === 'delivered' ? 'delivered' : 'in_transit',
                    'status_label' => $status === 'delivered' ? 'Delivered' : 'In transit',
                    'expected_on' => $placed->copy()->addDays(5),
                    'shipped_at' => $placed->copy()->addDays(1),
                    'delivered_at' => $status === 'delivered' ? $placed->copy()->addDays(4) : null,
                ]);
            }
        }
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<int, User>  $customers
     */
    private function reviews(array $products, array $customers): void
    {
        $plan = [
            [0, 5, 'Exactly the blue in the photograph', 'Wore it to my sister\'s reception. The zari is real and the drape falls beautifully.', true, true],
            [0, 4, 'Lovely, slightly heavier than I expected', 'No complaint about the weave — just know it is a proper silk weight.', true, true],
            [2, 5, 'Mysore silk done right', 'Light, plain, and the colour is deep. Second one from OJASVI.', true, true],
            [6, 5, 'My everyday saree now', 'Linen softens after the first wash. Bought the blush, will buy another shade.', true, true],
            [1, 4, 'Gold pallu is stunning', 'Took five days to reach Chennai. Packed well.', true, true],
            [3, 3, 'Good but the white marks easily', 'Fine saree, just not for a long day out.', true, false],
            [8, 5, 'Worth every rupee', 'Patola ikat, both sides identical. A keeper.', false, false],
        ];

        foreach ($plan as [$index, $rating, $title, $body, $verified, $approved]) {
            $customer = $customers[array_rand($customers)];

            Review::create([
                'product_id' => $products[$index]->id,
                'user_id' => $customer->id,
                'name' => $customer->name,
                'rating' => $rating,
                'title' => $title,
                'body' => $body,
                'is_verified' => $verified,
                'is_approved' => $approved,
                'created_at' => now()->subDays(random_int(1, 40)),
            ]);
        }
    }
}
