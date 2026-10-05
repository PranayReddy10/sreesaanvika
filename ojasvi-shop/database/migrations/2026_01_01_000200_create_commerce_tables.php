<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bags, orders and money.
 *
 * Two rules this schema exists to enforce.
 *
 * First, an order line keeps its own copy of the name, the design code and the
 * price. A product renamed or repriced next month must not rewrite what
 * somebody bought last month — an invoice is a record, not a view.
 *
 * Second, every payment attempt is a row. A shopper who fails once and
 * succeeds twice leaves three rows and one paid order, which is what makes a
 * disputed payment answerable.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();            // Home, Office
            $table->string('name');
            $table->string('phone', 20);
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('pincode', 10);
            $table->string('country', 2)->default('IN');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->index('pincode');
        });

        /*
         * A bag survives the browser. A guest's is found by the token in their
         * cookie; once they sign in it is claimed and the two are merged.
         */
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->nullable()->unique();
            $table->string('coupon_code')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('last_active_at');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('colourway_id')->nullable()->constrained('colourways')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            // One line per design-and-shade, so adding again adds quantity.
            $table->unique(['cart_id', 'product_id', 'colourway_id'], 'cart_line_unique');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Human-facing and never reused: OJ-2026-00041.
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status')->default('pending');
            // pending | confirmed | packed | shipped | delivered | cancelled | returned | refunded
            $table->string('payment_status')->default('unpaid');
            // unpaid | paid | failed | refunded | partially_refunded
            $table->string('payment_method')->nullable();   // razorpay | cod

            $table->string('email');
            $table->string('phone', 20);

            $table->decimal('items_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('offer_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('refunded_total', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');

            $table->string('coupon_code')->nullable();

            // Copied, not referenced: an address edited later must not rewrite
            // where a parcel was actually sent.
            $table->json('shipping_address');
            $table->json('billing_address')->nullable();

            $table->text('note')->nullable();           // what the shopper asked for
            $table->text('admin_note')->nullable();     // what the shop told itself

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index('email');
            $table->index('phone');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Nulled rather than cascaded: deleting a product must never delete
            // the history of it having been sold.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('colourway_id')->nullable()->constrained('colourways')->nullOnDelete();

            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('colourway_name')->nullable();
            $table->string('image')->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->decimal('unit_discount', 12, 2)->default(0);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);

            // Which offer made a unit free, and how much it saved.
            $table->foreignId('offer_id')->nullable();
            $table->string('offer_name')->nullable();
            $table->unsignedInteger('free_units')->default(0);

            $table->timestamps();
            $table->index('order_id');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from')->nullable();
            $table->string('to');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('razorpay');
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_signature')->nullable();
            $table->string('method')->nullable();        // upi | card | netbanking | wallet
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('created'); // created | authorised | captured | failed | refunded
            $table->string('failure_reason')->nullable();
            $table->json('payload')->nullable();          // what the gateway actually sent
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier')->default('delhivery');
            $table->string('awb')->nullable()->index();
            $table->string('status')->default('pending');
            // pending | manifested | picked | in_transit | out_for_delivery | delivered | undelivered | rto
            $table->string('status_label')->nullable();   // the courier's own wording
            $table->date('expected_on')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('timeline')->nullable();
            $table->timestamp('polled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'polled_at']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent');   // percent | fixed | free_shipping
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('min_spend', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->unsignedInteger('used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'coupon_redemptions', 'coupons', 'shipments', 'payments',
            'order_status_history', 'order_items', 'orders',
            'cart_items', 'carts', 'addresses',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
