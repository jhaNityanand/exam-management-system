<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_proctoring_policies')) {
            return;
        }

        Schema::table('exam_proctoring_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_proctoring_policies', 'lock_keyboard')) {
                $table->boolean('lock_keyboard')->default(false)->after('detect_devtools');
            }
            if (! Schema::hasColumn('exam_proctoring_policies', 'lock_mouse')) {
                $table->boolean('lock_mouse')->default(false)->after('lock_keyboard');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exam_proctoring_policies')) {
            return;
        }

        Schema::table('exam_proctoring_policies', function (Blueprint $table) {
            if (Schema::hasColumn('exam_proctoring_policies', 'lock_mouse')) {
                $table->dropColumn('lock_mouse');
            }
            if (Schema::hasColumn('exam_proctoring_policies', 'lock_keyboard')) {
                $table->dropColumn('lock_keyboard');
            }
        });
    }
};
