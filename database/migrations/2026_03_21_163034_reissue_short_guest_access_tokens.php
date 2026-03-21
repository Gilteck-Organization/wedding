<?php

use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('guests', 'access_token')) {
            return;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        foreach (Guest::query()->orderBy('id')->get() as $guest) {
            $hadQr = filled($guest->qr_code);
            $token = Guest::newAccessToken();
            $guest->access_token = $token;
            if ($hadQr) {
                $guest->qr_code = $appUrl.'/access-card/'.$token;
            }
            $guest->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tokens cannot be restored to previous values.
    }
};
