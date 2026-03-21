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
        if (! Schema::hasTable('guest_dedication_names') || ! Schema::hasTable('guests')) {
            return;
        }

        $guests = DB::table('guests')->select('id', 'name')->get();

        foreach ($guests as $guest) {
            $exists = DB::table('guest_dedication_names')
                ->where('guest_id', $guest->id)
                ->where('name', $guest->name)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('guest_dedication_names')->insert([
                'guest_id' => $guest->id,
                'name' => $guest->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not remove names on rollback.
    }
};
