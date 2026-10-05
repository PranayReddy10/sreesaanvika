<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What people type into the shop's own search box.
 *
 * The most useful thing a small shop can know: a search that found nothing is
 * somebody telling you what to buy next, in their own words. Worth a table of
 * its own because Google Analytics will not tell you how many results came
 * back, and "banarasi" finding nothing is a different fact from "banarasi"
 * finding twelve.
 *
 * Deliberately no user, no address and no session: this is about what the shop
 * is asked for, never about who asked.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('searches', function (Blueprint $table) {
            $table->id();
            $table->string('term', 120);
            // Lower-cased and squeezed, so "Pure Silk" and "pure  silk" group.
            $table->string('normalised', 120)->index();
            $table->unsignedInteger('results')->default(0);
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['normalised', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('searches');
    }
};
