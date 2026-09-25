<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Last known name per device (MAC) per router, captured from the
     * router's DHCP leases while the device is online. RADIUS accounting
     * only carries the MAC, so this is what lets the connection history
     * show a readable device name after the lease is gone.
     */
    public function up(): void
    {
        if (Schema::hasTable('device_names')) {
            return;
        }

        Schema::create('device_names', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('router_id');
            $table->string('mac_address', 17);
            $table->string('name', 191);
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['router_id', 'mac_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_names');
    }
};
