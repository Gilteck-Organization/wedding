<?php

use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (Guest::query()->whereNotNull('qr_code')->cursor() as $guest) {
            if ($guest->access_token === null || $guest->access_token === '') {
                continue;
            }

            $guest->qr_code = route('access-card.verify', $guest, absolute: true);
            $guest->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (Guest::query()->whereNotNull('qr_code')->cursor() as $guest) {
            if ($guest->access_token === null || $guest->access_token === '') {
                continue;
            }

            $guest->qr_code = route('access-card', $guest, absolute: true);
            $guest->saveQuietly();
        }
    }
};
