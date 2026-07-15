<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds module / thumbnail tracking used by Blog, News, and Profile uploads.
 * Safe to run against older installs that already created galleries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            if (! Schema::hasColumn('galleries', 'module')) {
                $table->string('module', 50)->nullable()->after('source');
                $table->index(['organization_id', 'module']);
            }
            if (! Schema::hasColumn('galleries', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('module');
            }
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            if (Schema::hasColumn('galleries', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
            if (Schema::hasColumn('galleries', 'module')) {
                $table->dropIndex(['organization_id', 'module']);
                $table->dropColumn('module');
            }
        });
    }
};
