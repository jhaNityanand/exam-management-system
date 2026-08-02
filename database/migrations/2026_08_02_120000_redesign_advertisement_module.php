<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign advertisements for the placement-based ad module.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Clear legacy rows — schema and placement model changed completely.
        if (Schema::hasTable('advertisements')) {
            DB::table('advertisements')->delete();
        }

        // Drop legacy composite index if present (name may vary by driver).
        try {
            Schema::table('advertisements', function (Blueprint $table) {
                $table->dropIndex(['organization_id', 'placement', 'status']);
            });
        } catch (\Throwable) {
            // Index may already be absent on fresh/partial installs.
        }

        Schema::table('advertisements', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'placement',
                'headline',
                'body',
                'code',
                'cta_label',
                'cta_url',
                'starts_at',
                'ends_at',
            ] as $column) {
                if (Schema::hasColumn('advertisements', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('advertisements', function (Blueprint $table) {
            if (Schema::hasColumn('advertisements', 'mobile_image_id')) {
                $table->dropConstrainedForeignId('mobile_image_id');
            }
        });

        Schema::table('advertisements', function (Blueprint $table) {
            if (! Schema::hasColumn('advertisements', 'title')) {
                $table->string('title')->nullable()->after('name');
            }
            if (! Schema::hasColumn('advertisements', 'target_url')) {
                $table->string('target_url', 500)->nullable()->after('image_id');
            }
            if (! Schema::hasColumn('advertisements', 'open_in_new_tab')) {
                $table->boolean('open_in_new_tab')->default(true)->after('target_url');
            }
            if (! Schema::hasColumn('advertisements', 'banner_size')) {
                $table->string('banner_size', 40)->nullable()->after('open_in_new_tab');
            }
            if (! Schema::hasColumn('advertisements', 'iframe_url')) {
                $table->string('iframe_url', 1000)->nullable()->after('banner_size');
            }
            if (! Schema::hasColumn('advertisements', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('iframe_url');
            }
            if (! Schema::hasColumn('advertisements', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
            if (! Schema::hasColumn('advertisements', 'is_responsive')) {
                $table->boolean('is_responsive')->default(true)->after('height');
            }
            if (! Schema::hasColumn('advertisements', 'html_code')) {
                $table->longText('html_code')->nullable()->after('is_responsive');
            }
            if (! Schema::hasColumn('advertisements', 'css_code')) {
                $table->longText('css_code')->nullable()->after('html_code');
            }
            if (! Schema::hasColumn('advertisements', 'js_code')) {
                $table->longText('js_code')->nullable()->after('css_code');
            }
            if (! Schema::hasColumn('advertisements', 'notes')) {
                $table->text('notes')->nullable()->after('js_code');
            }
        });

        // Normalize legacy type values.
        if (Schema::hasColumn('advertisements', 'type')) {
            DB::table('advertisements')->where('type', 'custom_html')->update(['type' => 'html']);
            DB::table('advertisements')->where('type', 'google_ads')->update(['type' => 'html']);
        }

        try {
            Schema::table('advertisements', function (Blueprint $table) {
                $table->index(['organization_id', 'type', 'status'], 'advertisements_org_type_status_index');
            });
        } catch (\Throwable) {
            // Index may already exist.
        }

        Schema::create('google_advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->longText('code');
            $table->string('ad_client')->nullable();
            $table->string('ad_slot')->nullable();
            $table->string('ad_format', 40)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status'], 'google_ads_org_status_index');
        });

        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('page_key', 80);
            $table->string('position_key', 80);
            $table->string('source_type', 20); // google|custom
            $table->foreignId('advertisement_id')->nullable()->constrained('advertisements')->nullOnDelete();
            $table->foreignId('google_advertisement_id')->nullable()->constrained('google_advertisements')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'page_key', 'position_key'], 'ad_placements_org_page_pos_index');
            $table->index(['organization_id', 'is_enabled'], 'ad_placements_org_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('google_advertisements');

        Schema::table('advertisements', function (Blueprint $table) {
            if (Schema::hasIndex('advertisements', 'advertisements_org_type_status_index')) {
                $table->dropIndex('advertisements_org_type_status_index');
            }
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'title',
                'target_url',
                'open_in_new_tab',
                'banner_size',
                'iframe_url',
                'width',
                'height',
                'is_responsive',
                'html_code',
                'css_code',
                'js_code',
                'notes',
            ] as $column) {
                if (Schema::hasColumn('advertisements', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('advertisements', function (Blueprint $table) {
            if (! Schema::hasColumn('advertisements', 'placement')) {
                $table->string('placement', 80)->nullable()->after('type');
            }
            if (! Schema::hasColumn('advertisements', 'headline')) {
                $table->string('headline')->nullable();
            }
            if (! Schema::hasColumn('advertisements', 'body')) {
                $table->text('body')->nullable();
            }
            if (! Schema::hasColumn('advertisements', 'code')) {
                $table->longText('code')->nullable();
            }
            if (! Schema::hasColumn('advertisements', 'cta_label')) {
                $table->string('cta_label')->nullable();
            }
            if (! Schema::hasColumn('advertisements', 'cta_url')) {
                $table->string('cta_url')->nullable();
            }
            if (! Schema::hasColumn('advertisements', 'mobile_image_id')) {
                $table->foreignId('mobile_image_id')->nullable()->constrained('galleries')->nullOnDelete();
            }
            if (! Schema::hasColumn('advertisements', 'starts_at')) {
                $table->timestamp('starts_at')->nullable();
            }
            if (! Schema::hasColumn('advertisements', 'ends_at')) {
                $table->timestamp('ends_at')->nullable();
            }
        });
    }
};
