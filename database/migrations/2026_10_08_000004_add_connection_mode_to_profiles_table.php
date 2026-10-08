<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a profile's devices stay online after logging in: "binding"
     * moves each device onto a bypass ip-binding + simple queue (no
     * re-login until the voucher ends), "hotspot" leaves it a plain
     * hotspot session under RADIUS.
     */
    public function up(): void
    {
        if (Schema::hasColumn('profiles', 'connection_mode')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->enum('connection_mode', ['binding', 'hotspot'])->default('binding')->after('bandwidth_mode');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('connection_mode');
        });
    }
};
