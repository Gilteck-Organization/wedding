<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->string('whatsapp_message_id')->nullable()->after('is_approved');
            $table->string('whatsapp_status', 32)->nullable()->after('whatsapp_message_id');
            $table->timestamp('whatsapp_status_at')->nullable()->after('whatsapp_status');
            $table->timestamp('whatsapp_last_sent_at')->nullable()->after('whatsapp_status_at');
            $table->unsignedTinyInteger('whatsapp_attempts')->default(0)->after('whatsapp_last_sent_at');
            $table->text('whatsapp_error')->nullable()->after('whatsapp_attempts');

            $table->index('whatsapp_message_id');
            $table->index('whatsapp_status');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropIndex(['whatsapp_message_id']);
            $table->dropIndex(['whatsapp_status']);
            $table->dropColumn([
                'whatsapp_message_id',
                'whatsapp_status',
                'whatsapp_status_at',
                'whatsapp_last_sent_at',
                'whatsapp_attempts',
                'whatsapp_error',
            ]);
        });
    }
};
