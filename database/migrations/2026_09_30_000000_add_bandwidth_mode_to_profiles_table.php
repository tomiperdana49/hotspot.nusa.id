<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a profile's rate applies to a voucher's devices: "per_device"
     * gives every device the full rate, "shared" caps all of a voucher's
     * devices together at that rate.
     */
    public function up(): void
    {
        if (Schema::hasColumn('profiles', 'bandwidth_mode')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->enum('bandwidth_mode', ['per_device', 'shared'])->default('per_device')->after('rate_up');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('bandwidth_mode');
        });
    }
};
