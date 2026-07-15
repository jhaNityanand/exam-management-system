<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['news_id', 'gallery_id']);
            $table->index(['news_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_banners');
    }
};
