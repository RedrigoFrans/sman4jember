<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_collections', function (Blueprint $table) {
            $table->string('collection_type')->default('catalog')->after('book_id');

            $table->dropUnique('book_collections_nik_book_id_unique');
            $table->unique(
                ['nik', 'book_id', 'collection_type'],
                'book_collections_nik_book_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('book_collections', function (Blueprint $table) {
            $table->dropUnique('book_collections_nik_book_type_unique');
            $table->unique(['nik', 'book_id']);
            $table->dropColumn('collection_type');
        });
    }
};