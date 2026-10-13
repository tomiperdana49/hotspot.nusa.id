<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login-page extras that every guest sees, whatever their phone does
     * after login: a banner (image and/or text) and up to three links,
     * whose hosts go into the router's walled garden. hold_seconds is how
     * long hold_popup keeps the phone's login pop-up open.
     */
    public function up(): void
    {
        Schema::table('hotspot_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('hotspot_templates', 'hold_seconds')) {
                $table->unsignedTinyInteger('hold_seconds')->default(5)->after('hold_popup');
            }
            if (! Schema::hasColumn('hotspot_templates', 'banner_path')) {
                $table->string('banner_path')->nullable()->after('background_path');
            }
            if (! Schema::hasColumn('hotspot_templates', 'banner_text')) {
                $table->string('banner_text', 255)->nullable()->after('banner_path');
            }
            if (! Schema::hasColumn('hotspot_templates', 'links')) {
                $table->json('links')->nullable()->after('banner_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_templates', function (Blueprint $table) {
            foreach (['hold_seconds', 'banner_path', 'banner_text', 'links'] as $column) {
                if (Schema::hasColumn('hotspot_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
