<?php

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
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->after('id')->constrained('hotels')->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE clients c
            SET c.hotel_id = (
                SELECT MIN(a.hotel_id)
                FROM reservations r
                INNER JOIN rooms ro ON ro.id = r.room_id
                INNER JOIN apartments a ON a.id = ro.apartment_id
                WHERE r.client_id = c.id
            )
            WHERE c.hotel_id IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hotel_id');
        });
    }
};
