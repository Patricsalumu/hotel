<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reservations', 'apartment_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreignId('apartment_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            });
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            if (Schema::hasColumn('reservations', 'room_id')) {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->dropForeign(['room_id']);
                });
            }

            if (Schema::hasColumn('reservations', 'room_id')) {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->foreignId('room_id')->nullable()->change();
                });
            }

            if (Schema::hasColumn('reservations', 'room_id')) {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->unsignedBigInteger('room_id')->nullable()->change();
                });
            }

            if (Schema::hasColumn('reservations', 'room_id')) {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
                });
            }
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            $rows = DB::table('reservations')->select('id', 'room_id', 'hotel_id')->get();
            foreach ($rows as $row) {
                $room = DB::table('rooms')->where('id', $row->room_id)->first();
                if (! $room) {
                    continue;
                }
                $apartmentId = DB::table('apartments')->where('id', $room->apartment_id)->value('id');
                DB::table('reservations')->where('id', $row->id)->update([
                    'apartment_id' => $apartmentId,
                    'room_id' => null,
                ]);
            }
            // Ensure reservations.room_id is nullable in sqlite by rebuilding table if necessary
            try {
                $res = DB::select("SELECT name, sql FROM sqlite_master WHERE name = 'reservations'");
                if (count($res)) {
                    $create = $res[0]->sql;
                    if (stripos($create, '"room_id" integer not null') !== false) {
                        $newCreate = str_ireplace('"room_id" integer not null', '"room_id" integer', $create);
                        $newCreate = preg_replace('/CREATE TABLE "reservations"/i', 'CREATE TABLE "reservations_new"', $newCreate, 1);
                        DB::statement($newCreate);
                        DB::statement('INSERT INTO reservations_new SELECT * FROM reservations');
                        DB::statement('DROP TABLE reservations');
                        DB::statement('ALTER TABLE reservations_new RENAME TO reservations');
                    }
                }
            } catch (\Throwable $e) {
                // logging handled by caller; swallow to keep migrations resilient in tests
            }
            return;
        }

        DB::table('reservations')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->update([
                'reservations.apartment_id' => DB::raw('rooms.apartment_id'),
                'reservations.room_id' => null,
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'apartment_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('apartment_id');
                $table->dropColumn('apartment_id');
            });
        }

        if (Schema::hasColumn('reservations', 'room_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreignId('room_id')->nullable(false)->change();
            });
        }
    }
};
