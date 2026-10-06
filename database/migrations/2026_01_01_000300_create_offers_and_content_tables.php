<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offers, storefront content, and the things customers leave behind.
 */
return new class extends Migration {
    public function up(): void
    {
        /*
         * Buy 2 Get 1 Free and its relatives — no code to type. The shop picks
         * the eligible pieces; when enough of them are in the bag the cheapest
         * becomes free, which is the promise the banner makes.
         */
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('headline')->nullable();     // what the shopper is told
            $table->string('kind')->default('bogo');    // bogo | tiers
            $table->unsignedSmallInteger('buy')->default(2);
            $table->unsignedSmallInteger('get')->default(1);
            // 100 means free; anything less is a discount on the cheapest.
            $table->unsignedSmallInteger('percent')->default(100);
            $table->boolean('repeats')->default(true);
            // Quantity breaks, as [{"qty":3,"percent":10}, ...]
            $table->json('tiers')->nullable();
            $table->boolean('applies_to_all')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('offer_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['offer_id', 'product_id']);
        });

        Schema::create('offer_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['offer_id', 'category_id']);
        });

        /*
         * Homepage sections, in the order the shop wants them. Keeping this in
         * the database rather than in code is what lets the shop rearrange its
         * own front page without a developer.
         */
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('key');                      // hero | featured | collection | lookbook | band
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->text('subheading')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['is_visible', 'position']);
        });

        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->text('text')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->string('image')->nullable();
            $table->string('align')->default('left');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('images')->nullable();
            // Only a review tied to a delivered order earns this.
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        /*
         * Settings the shop edits: brand name, contact details, the free
         * shipping threshold, the announcement bar. One row per key so a
         * single setting can be written without rewriting the rest.
         */
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');   // string | text | json | bool | int | money | image
            $table->timestamps();

            $table->index('group');
        });

        /*
         * Pincode serviceability and shipping charges, so the shop can answer
         * "do you deliver to me?" before checkout rather than after.
         */
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('pincodes')->nullable();        // prefixes, e.g. ["500", "110"]
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('free_from', 12, 2)->nullable();
            $table->unsignedSmallInteger('days_min')->nullable();
            $table->unsignedSmallInteger('days_max')->nullable();
            $table->boolean('cod_allowed')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'shipping_zones', 'settings', 'newsletter_subscribers', 'wishlist_items',
            'reviews', 'slides', 'sections', 'offer_category', 'offer_product', 'offers',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
