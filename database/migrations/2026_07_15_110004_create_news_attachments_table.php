<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['news_id', 'gallery_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_attachments');
    }
};
