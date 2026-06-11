<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->string('whatsapp_reminder_message_id')->nullable()->after('whatsapp_error');
            $table->timestamp('whatsapp_reminder_sent_at')->nullable()->after('whatsapp_reminder_message_id');
            $table->text('whatsapp_reminder_error')->nullable()->after('whatsapp_reminder_sent_at');

            $table->index('whatsapp_reminder_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropIndex(['whatsapp_reminder_message_id']);
            $table->dropColumn([
                'whatsapp_reminder_message_id',
                'whatsapp_reminder_sent_at',
                'whatsapp_reminder_error',
            ]);
        });
    }
};
