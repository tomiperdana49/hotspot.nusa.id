<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which of the client's routers a profile's vouchers may log in on:
     * every router ("all"), or only those listed in profile_router
     * ("selected"). FreeRADIUS checks this in authorize_check_query, so a
     * "selected" profile left with no routers logs in nowhere rather than
     * everywhere.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'router_scope')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->enum('router_scope', ['all', 'selected'])->default('all')->after('connection_mode');
            });
        }

        if (! Schema::hasTable('profile_router')) {
            Schema::create('profile_router', function (Blueprint $table) {
                $table->unsignedBigInteger('profile_id');
                $table->unsignedBigInteger('router_id');

                $table->primary(['profile_id', 'router_id']);
                $table->index('router_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_router');

        if (Schema::hasColumn('profiles', 'router_scope')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropColumn('router_scope');
            });
        }
    }
};
