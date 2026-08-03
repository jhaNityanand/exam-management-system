<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fingerprint for duplicate prevention across the central gallery library.
 * Same bytes within an organization reuse the existing gallery row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('file_size');
            $table->index(['organization_id', 'content_hash'], 'galleries_org_content_hash_index');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropIndex('galleries_org_content_hash_index');
            $table->dropColumn('content_hash');
        });
    }
};
