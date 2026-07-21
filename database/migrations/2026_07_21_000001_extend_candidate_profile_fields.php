<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 64)->nullable()->unique()->after('name');
            }
        });

        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('profiles', 'gender')) {
                $table->string('gender', 32)->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('profiles', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('social_links');
            }
            if (! Schema::hasColumn('profiles', 'privacy_settings')) {
                $table->json('privacy_settings')->nullable()->after('notification_preferences');
            }
        });

        if (! Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('event', 64);
                $table->string('category', 48)->default('general');
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['user_id', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');

        Schema::table('profiles', function (Blueprint $table) {
            foreach (['privacy_settings', 'notification_preferences', 'gender', 'date_of_birth'] as $column) {
                if (Schema::hasColumn('profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
