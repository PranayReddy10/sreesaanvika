<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The front page does not need a photograph of its own after all.
 *
 * It was added so that a picture on the front page would not greet the
 * shopper again at the top of the saree's page — but the shop already has
 * "Pictures of this saree" for the front page, and the repetition is better
 * cured at the other end: the saree's page now shows the saree worn and the
 * shade chosen, and leaves those pictures to the front page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('home_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('home_image')->nullable()->after('model_image');
        });
    }
};
