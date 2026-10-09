<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two photographs that are not part of the gallery.
 *
 * A saree shop cannot photograph a model wearing every piece it sells, so one
 * picture of this saree worn stands for the rest — it opens the saree's page,
 * and it is what a shopper sees when she picks a shade nobody has photographed
 * separately.
 *
 * The other is for the front page only. The row there is a full-width
 * editorial photograph, and the shop was finding the same picture again at the
 * top of the saree's own page a moment after clicking it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('model_image')->nullable()->after('description');
            $table->string('home_image')->nullable()->after('model_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['model_image', 'home_image']);
        });
    }
};
