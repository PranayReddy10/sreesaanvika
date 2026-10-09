<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A switch per shade for the picture of the saree worn.
 *
 * One photograph of the saree on somebody stands in for every shade nobody
 * has photographed separately — but not every shade. A pomegranate saree
 * shown under a picture of the indigo one being worn is a photograph that
 * tells the shopper the wrong thing, and the shop knows which shades those
 * are better than any rule here could.
 *
 * On by default: the shades already in the shop were set up expecting it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colourways', function (Blueprint $table) {
            $table->boolean('show_worn_picture')->default(true)->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('colourways', function (Blueprint $table) {
            $table->dropColumn('show_worn_picture');
        });
    }
};
