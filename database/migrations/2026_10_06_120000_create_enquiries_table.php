<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Somebody writing in.
 *
 * The contact page gave a telephone number, an address and an email, which
 * suits a shopper who is ready to pick up the phone and nobody else. A box to
 * type in catches the rest — the question at eleven at night about whether the
 * blouse piece is included.
 *
 * Kept as well as sent. An email can be deleted by accident, read on a phone
 * and forgotten, or land in a folder nobody opens; a row cannot, and the shop
 * can see at a glance what is still unanswered.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            // Most of what a shop is asked about is an order already placed.
            $table->string('order_number')->nullable();
            $table->text('message');
            $table->timestamp('answered_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('answered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
