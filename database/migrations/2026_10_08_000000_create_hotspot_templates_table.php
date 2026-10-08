<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One hotspot login page design per client, pushed to each of the
     * client's routers on demand. The router columns remember where it
     * was put and which html-directory each hotspot profile had before,
     * so "restore default" can put that back.
     */
    public function up(): void
    {
        if (! Schema::hasTable('hotspot_templates')) {
            Schema::create('hotspot_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->unique();
                $table->string('token', 40)->unique();
                $table->string('theme', 20)->default('modern');
                $table->string('page_lang', 2)->default('id');
                $table->string('title', 60);
                $table->string('welcome_text', 255)->nullable();
                $table->string('primary_color', 7)->default('#059669');
                $table->string('login_mode', 20)->default('userpass');
                $table->string('logo_path')->nullable();
                $table->string('contact', 100)->nullable();
                $table->string('footer_text', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('routers', 'login_template_applied_at')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->string('login_template_dir', 64)->nullable();
                $table->timestamp('login_template_applied_at')->nullable();
                $table->text('html_directory_backup')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_templates');

        if (Schema::hasColumn('routers', 'login_template_applied_at')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->dropColumn(['login_template_dir', 'login_template_applied_at', 'html_directory_backup']);
            });
        }
    }
};
