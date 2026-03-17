<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_packages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('hotels_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('rooms_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->string('name');
            $table->string('duration');
            $table->date('stay_period_start');
            $table->date('stay_period_end');
            $table->integer('contract_rate');
            $table->integer('markup');
            $table->string('booking_code')->nullable();
            $table->longText('benefits')->nullable();
            $table->longText('benefits_traditional')->nullable();
            $table->longText('benefits_simplified')->nullable();
            $table->longText('include')->nullable();
            $table->longText('include_traditional')->nullable();
            $table->longText('include_simplified')->nullable();
            $table->longText('additional_info')->nullable();
            $table->longText('additional_info_traditional')->nullable();
            $table->longText('additional_info_simplified')->nullable();
            $table->string('status');
            $table->integer('author');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_packages');
    }
};
