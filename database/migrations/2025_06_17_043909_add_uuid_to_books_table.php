<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add uuid column only if it doesn't exist
        if (!Schema::hasColumn('books', 'uuid')) {
            Schema::table('books', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Generate UUIDs for existing books that don't have them
        $books = \DB::table('books')->whereNull('uuid')->orWhere('uuid', '')->get();

        foreach ($books as $book) {
            \DB::table('books')
                ->where('id', $book->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // Add unique constraint and index if they don't exist
        $indexExists = collect(\DB::select("SHOW INDEX FROM books WHERE Column_name = 'uuid'"))->isNotEmpty();

        if (!$indexExists) {
            Schema::table('books', function (Blueprint $table) {
                $table->unique('uuid');
                $table->index('uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
