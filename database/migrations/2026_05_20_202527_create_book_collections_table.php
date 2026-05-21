<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_collections', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16);
            $table->string('book_id');
            $table->string('title');
            $table->string('author')->nullable();
            $table->text('cover_image')->nullable();
            $table->json('book_data')->nullable();
            $table->timestamps();

            $table->unique(['nik', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_collections');
    }
};