<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batches whose vouchers use the username as the password too, for
     * the one-field "voucher code" hotspot login template.
     */
    public function up(): void
    {
        if (Schema::hasColumn('user_batches', 'password_as_username')) {
            return;
        }

        Schema::table('user_batches', function (Blueprint $table) {
            $table->boolean('password_as_username')->default(false)->after('same_password');
        });
    }

    public function down(): void
    {
        Schema::table('user_batches', function (Blueprint $table) {
            $table->dropColumn('password_as_username');
        });
    }
};
