<?php

use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->unique();
        });

        $appUrl = rtrim((string) config('app.url'), '/');

        foreach (DB::table('guests')->orderBy('id')->get() as $row) {
            $token = Guest::newAccessToken();

            $updates = [
                'access_token' => $token,
            ];

            if ($row->qr_code !== null && $row->qr_code !== '') {
                $updates['qr_code'] = $appUrl.'/access-card/'.$token;
            }

            DB::table('guests')->where('id', $row->id)->update($updates);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('access_token');
        });
    }
};
