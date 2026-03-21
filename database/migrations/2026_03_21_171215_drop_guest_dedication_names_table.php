<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('guest_dedication_names') || ! Schema::hasTable('access_names')) {
            Schema::dropIfExists('guest_dedication_names');

            return;
        }

        $rows = DB::table('guest_dedication_names')->select('name')->distinct()->get();

        foreach ($rows as $row) {
            $exists = DB::table('access_names')->where('name', $row->name)->exists();
            if ($exists) {
                continue;
            }

            DB::table('access_names')->insert([
                'name' => $row->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::dropIfExists('guest_dedication_names');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreating guest_dedication_names is not supported.
    }
};
