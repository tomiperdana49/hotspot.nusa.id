<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where alogin.html sends the customer after a successful login.
     * Null means the hotspot status page.
     */
    public function up(): void
    {
        if (Schema::hasColumn('hotspot_templates', 'redirect_url')) {
            return;
        }

        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->string('redirect_url', 255)->nullable()->after('login_mode');
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->dropColumn('redirect_url');
        });
    }
};
