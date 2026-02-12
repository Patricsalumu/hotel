<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('type')->default('simple');
            $table->decimal('price_per_night', 12, 2);
            $table->string('dimension')->nullable();
            $table->string('shape')->nullable();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->enum('status', ['available', 'reserved', 'occupied'])->default('available');
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();

            $table->unique(['apartment_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
