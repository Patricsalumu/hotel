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
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('reservation_number')->nullable()->after('id');
            $table->softDeletes();
        });

        DB::table('reservations')
            ->join('rooms', 'rooms.id', '=', 'reservations.room_id')
            ->join('apartments', 'apartments.id', '=', 'rooms.apartment_id')
            ->update(['reservations.hotel_id' => DB::raw('apartments.hotel_id')]);

        $rows = DB::table('reservations')
            ->select('id', 'hotel_id')
            ->whereNotNull('hotel_id')
            ->orderBy('hotel_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $counters = [];
        foreach ($rows as $row) {
            $hotelId = (int) $row->hotel_id;
            $counters[$hotelId] = ($counters[$hotelId] ?? 0) + 1;

            DB::table('reservations')
                ->where('id', $row->id)
                ->update(['reservation_number' => $counters[$hotelId]]);
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['hotel_id', 'reservation_number'], 'reservations_hotel_number_unique');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_hotel_number_unique');
            $table->dropConstrainedForeignId('hotel_id');
            $table->dropColumn('reservation_number');
            $table->dropSoftDeletes();
        });
    }
};
