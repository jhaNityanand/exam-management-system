<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_tag_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('news_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['news_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_tag_relations');
    }
};
