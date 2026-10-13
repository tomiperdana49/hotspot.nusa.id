<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Websites a client blocks on one router. The router holds the
     * matching firewall rules (see MikrotikConnector::syncBlockedSites());
     * this table is the list they are rebuilt from.
     */
    public function up(): void
    {
        if (Schema::hasTable('router_blocked_sites')) {
            return;
        }

        Schema::create('router_blocked_sites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('router_id');
            $table->string('domain', 253);
            $table->timestamps();

            $table->unique(['router_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_blocked_sites');
    }
};
