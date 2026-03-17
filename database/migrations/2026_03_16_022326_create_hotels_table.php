<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('region');
            $table->string('address');
            $table->integer('airport_duration')->nullable();
            $table->integer('airport_distance')->nullable();
            $table->string('contact_person');
            $table->string('phone');
            $table->longText('description')->nullable();
            $table->longText('description_traditional')->nullable();
            $table->longText('description_simplified')->nullable();
            $table->longText('facility')->nullable();
            $table->longText('additional_info')->nullable();
            $table->longText('wedding_info')->nullable();
            $table->longText('entrance_fee')->nullable();
            $table->longText('wedding_cancellation_policy')->nullable();
            $table->string('status');
            $table->text('cover');
            $table->integer('author_id');
            $table->string('web')->nullable();
            $table->string('min_stay')->nullable();
            $table->string('max_stay')->nullable();
            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->string('map')->nullable();
            $table->string('benefits')->nullable();
            $table->string('optional_rate')->nullable();
            $table->longText('cancellation_policy')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
