<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether applying the template also holds the phone's login pop-up
     * open for a few seconds after login, so the redirect page shows
     * (see MikrotikConnector::syncPortalHold()).
     */
    public function up(): void
    {
        if (Schema::hasColumn('hotspot_templates', 'hold_popup')) {
            return;
        }

        Schema::table('hotspot_templates', function (Blueprint $table) {
            $table->boolean('hold_popup')->default(false)->after('redirect_url');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotspot_templates', 'hold_popup')) {
            Schema::table('hotspot_templates', function (Blueprint $table) {
                $table->dropColumn('hold_popup');
            });
        }
    }
};
