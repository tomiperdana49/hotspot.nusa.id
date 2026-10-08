<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional background image for the hotspot login pages, stored next
     * to the logo and pushed to the router with the other files.
     */
    public function up(): void
    {
        if (Schema::hasColumn('hotspot_templates', 'background_path')) {
            return;
        }

        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->string('background_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->dropColumn('background_path');
        });
    }
};
