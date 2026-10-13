<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A client's template with router_id NULL is their default; a row with
     * router_id set is that router's own design and takes precedence when
     * the template is applied to it. Each row has its own token, so the
     * router fetches its files the same way either way.
     */
    public function up(): void
    {
        if (Schema::hasColumn('hotspot_templates', 'router_id')) {
            return;
        }

        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('router_id')->nullable()->after('client_id');
            $table->index(['client_id', 'router_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hotspot_templates', 'router_id')) {
            return;
        }

        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'router_id']);
            $table->dropColumn('router_id');
        });
    }
};
