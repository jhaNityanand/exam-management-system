<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            if (! Schema::hasColumn('advertisements', 'type')) {
                $table->string('type', 30)->default('banner')->after('name');
            }
            if (! Schema::hasColumn('advertisements', 'code')) {
                $table->longText('code')->nullable()->after('body');
            }
            if (! Schema::hasColumn('advertisements', 'mobile_image_id')) {
                $table->foreignId('mobile_image_id')->nullable()->after('image_id')->constrained('galleries')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            if (Schema::hasColumn('advertisements', 'mobile_image_id')) {
                $table->dropConstrainedForeignId('mobile_image_id');
            }
            if (Schema::hasColumn('advertisements', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('advertisements', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
