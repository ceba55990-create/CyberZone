<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name', 100);
            $table->string('category_info', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};
