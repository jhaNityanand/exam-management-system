<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['blog_id', 'gallery_id']);
            $table->index(['blog_id', 'sort_order']);
        });

        // Backfill primary banners into the ordered multi-banner table.
        if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'banner_image_id')) {
            $rows = DB::table('blogs')
                ->whereNotNull('banner_image_id')
                ->select('id', 'banner_image_id', 'created_at', 'updated_at')
                ->get();

            foreach ($rows as $row) {
                DB::table('blog_banners')->insertOrIgnore([
                    'blog_id' => $row->id,
                    'gallery_id' => $row->banner_image_id,
                    'sort_order' => 0,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_banners');
    }
};
