<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_promos', function (Blueprint $table) {
            $table->id();
            $table->string('promotion_type')->nullable();
            $table->longText('quotes')->nullable();
            $table->foreignId('hotels_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('rooms_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->string('name');
            $table->date('book_periode_start');
            $table->date('book_periode_end');
            $table->date('periode_start');
            $table->date('periode_end');
            $table->integer('minimum_stay');
            $table->integer('contract_rate');
            $table->integer('markup');
            $table->string('booking_code')->nullable();
            $table->longText('benefits')->nullable();
            $table->longText('benefits_traditional')->nullable();
            $table->longText('benefits_simplified')->nullable();
            $table->boolean('email_status');
            $table->boolean('send_to_specific_email');
            $table->longText('specific_email');
            $table->string('status');
            $table->integer('author');
            $table->longText('include')->nullable();
            $table->longText('include_traditional')->nullable();
            $table->longText('include_simplified')->nullable();
            $table->longText('additional_info')->nullable();
            $table->longText('additional_info_traditional')->nullable();
            $table->longText('additional_info_simplified')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_promos');
    }
};
