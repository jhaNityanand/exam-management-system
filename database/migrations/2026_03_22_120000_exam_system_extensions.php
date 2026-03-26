<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('logo')->nullable()->after('description');
            $table->string('banner')->nullable()->after('logo');
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('organization_id')->constrained('categories')->cascadeOnDelete();
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->json('correct_answers')->nullable()->after('correct_answer');
            $table->boolean('allows_multiple')->default(false)->after('type');
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->timestamp('scheduled_start')->nullable()->after('status');
            $table->timestamp('scheduled_end')->nullable()->after('scheduled_start');
            $table->decimal('negative_mark_per_question', 8, 4)->default(0)->after('scheduled_end');
            $table->boolean('shuffle_questions')->default(false)->after('negative_mark_per_question');
            $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
            $table->string('exam_mode', 32)->default('standard')->after('shuffle_options');
            $table->json('category_question_rules')->nullable()->after('exam_mode');
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });

        Schema::create('exam_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('marks_override')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['exam_id', 'question_id']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('address_line1')->nullable()->after('avatar');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state_region')->nullable()->after('city');
            $table->string('postal_code', 32)->nullable()->after('state_region');
            $table->string('country', 2)->nullable()->after('postal_code');
            $table->foreignId('default_organization_id')->nullable()->after('country')->constrained('organizations')->nullOnDelete();
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });

        Schema::create('user_app_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme', 16)->default('system');
            $table->boolean('sidebar_collapsed')->default(false);
            $table->json('preferences')->nullable();
            $table->timestamps();
        });

        Schema::table('user_organizations', function (Blueprint $table) {
            $table->json('updated_by_history')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_app_settings');
        Schema::dropIfExists('exam_question');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['default_organization_id']);
            $table->dropColumn([
                'address_line1', 'address_line2', 'city', 'state_region', 'postal_code', 'country',
                'default_organization_id', 'updated_by_history',
            ]);
        });

        Schema::table('user_organizations', function (Blueprint $table) {
            $table->dropColumn('updated_by_history');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_start', 'scheduled_end', 'negative_mark_per_question',
                'shuffle_questions', 'shuffle_options', 'exam_mode', 'category_question_rules',
                'updated_by_history',
            ]);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['correct_answers', 'allows_multiple', 'updated_by_history']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'updated_by_history']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'logo', 'banner', 'updated_by_history']);
        });
    }
};
