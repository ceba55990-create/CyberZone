<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')
                ->constrained('blog', 'blog_id')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 200);
            $table->string('description', 500)->nullable();
            $table->text('text')->nullable();
            $table->timestamps();
            $table->unique(['blog_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_translations');
    }
};
