<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->string('whatsapp_thankyou_message_id')->nullable()->after('whatsapp_reminder_error');
            $table->timestamp('whatsapp_thankyou_sent_at')->nullable()->after('whatsapp_thankyou_message_id');
            $table->text('whatsapp_thankyou_error')->nullable()->after('whatsapp_thankyou_sent_at');

            $table->index('whatsapp_thankyou_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropIndex(['whatsapp_thankyou_message_id']);
            $table->dropColumn([
                'whatsapp_thankyou_message_id',
                'whatsapp_thankyou_sent_at',
                'whatsapp_thankyou_error',
            ]);
        });
    }
};
