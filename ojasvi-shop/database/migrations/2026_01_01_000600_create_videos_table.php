<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short films of a saree being worn.
 *
 * A still photograph cannot show how a silk falls, and that is most of what a
 * shopper is trying to judge.
 *
 * One row, two places, independently: a film can belong to a saree, sit in the
 * front page's reel, or do both — which is the usual case, because a film of a
 * saree worn well is the best advertisement for that saree there is, and the
 * shop should not have to upload it twice to have it in both places.
 *
 * Either an uploaded file or an address elsewhere, never both. Shared hosting
 * has a modest disk and a modest upload limit, so a shop with long films can
 * keep them on a bucket or a CDN and still show them here.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            // Which saree it is of, if any. Set means it shows at the foot of
            // that saree's own page.
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();

            // And whether it is also in the front page's reel. The two are
            // separate questions, so they are separate columns.
            $table->boolean('on_home')->default(true);

            $table->string('title')->nullable();
            $table->string('caption')->nullable();

            $table->string('path')->nullable();      // uploaded to this shop
            $table->string('url')->nullable();       // or living somewhere else
            // The frame shown before it loads, so the page is never a grey box.
            $table->string('poster')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_visible', 'position']);
            $table->index(['on_home', 'is_visible', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
