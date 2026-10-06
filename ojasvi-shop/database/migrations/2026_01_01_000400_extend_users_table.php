<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customers and staff share one table; what separates them is is_admin, which
 * is the only thing standing between a shopper and the admin panel.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('avatar')->nullable()->after('is_admin');
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();

            $table->index('phone');
            $table->index('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'is_admin', 'avatar', 'last_login_at', 'deleted_at']);
        });
    }
};
