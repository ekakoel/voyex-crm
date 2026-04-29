<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 80);
            $table->date('notify_date');
            $table->timestamps();

            $table->unique(['inquiry_id', 'user_id', 'type', 'notify_date'], 'inq_notif_unique_per_day');
            $table->index(['type', 'notify_date'], 'inq_notif_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_notification_logs');
    }
};

