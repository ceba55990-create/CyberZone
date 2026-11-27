<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog', function (Blueprint $table) {
            $table->id('blog_id');
            $table->string('title', 200);
            $table->string('description', 500)->nullable();
            $table->text('text')->nullable();
            $table->foreignId('category_id')->nullable()
                ->constrained('category', 'category_id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('dt_created')->useCurrent();
            $table->timestamp('dt_updated')->useCurrent()->useCurrentOnUpdate();
            $table->index('dt_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog');
    }
};
