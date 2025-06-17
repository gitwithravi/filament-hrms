<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add uuid column only if it doesn't exist
        if (!Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Generate UUIDs for existing users that don't have them
        // Use DB query to avoid model issues during migration
        $users = \DB::table('users')->whereNull('uuid')->orWhere('uuid', '')->get();

        foreach ($users as $user) {
            \DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // Add unique constraint and index if they don't exist
        $indexExists = collect(\DB::select("SHOW INDEX FROM users WHERE Column_name = 'uuid'"))->isNotEmpty();

        if (!$indexExists) {
            Schema::table('users', function (Blueprint $table) {
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
