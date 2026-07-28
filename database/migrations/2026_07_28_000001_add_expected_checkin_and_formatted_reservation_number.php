<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement(<<<'SQL'
                CREATE TABLE reservations_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    client_id INTEGER NOT NULL,
                    room_id INTEGER NOT NULL,
                    manager_id INTEGER NOT NULL,
                    expected_checkin_date DATE NULL,
                    checkin_date DATE NULL,
                    expected_checkout_date DATE NULL,
                    actual_checkout_date DATE NULL,
                    status TEXT NOT NULL,
                    payment_status TEXT NOT NULL,
                    total_amount NUMERIC(12,2) NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    hotel_id INTEGER NULL,
                    reservation_number TEXT NULL,
                    deleted_at DATETIME NULL,
                    FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
                    FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE,
                    FOREIGN KEY(manager_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY(hotel_id) REFERENCES hotels(id) ON DELETE SET NULL,
                    UNIQUE(hotel_id, reservation_number)
                );
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO reservations_new (
                    id, client_id, room_id, manager_id, expected_checkin_date, checkin_date,
                    expected_checkout_date, actual_checkout_date, status, payment_status,
                    total_amount, created_at, updated_at, hotel_id, reservation_number, deleted_at
                )
                SELECT
                    id,
                    client_id,
                    room_id,
                    manager_id,
                    checkin_date,
                    CASE WHEN status = 'reserved' THEN NULL ELSE checkin_date END,
                    expected_checkout_date,
                    actual_checkout_date,
                    status,
                    payment_status,
                    total_amount,
                    created_at,
                    updated_at,
                    hotel_id,
                    CASE WHEN reservation_number IS NOT NULL THEN substr('000000' || reservation_number, -6) ELSE NULL END,
                    deleted_at
                FROM reservations;
            SQL);

            DB::statement('DROP TABLE reservations');
            DB::statement('ALTER TABLE reservations_new RENAME TO reservations');
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->date('expected_checkin_date')->nullable()->after('manager_id');
            });

            DB::statement('ALTER TABLE reservations MODIFY reservation_number VARCHAR(6) NULL');
            DB::statement('ALTER TABLE reservations MODIFY checkin_date DATE NULL');
            DB::statement('UPDATE reservations SET expected_checkin_date = checkin_date WHERE expected_checkin_date IS NULL');
            DB::statement('UPDATE reservations SET checkin_date = NULL WHERE status = "reserved"');
            DB::statement('UPDATE reservations SET reservation_number = LPAD(reservation_number, 6, "0") WHERE reservation_number IS NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement(<<<'SQL'
                CREATE TABLE reservations_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    client_id INTEGER NOT NULL,
                    room_id INTEGER NOT NULL,
                    manager_id INTEGER NOT NULL,
                    checkin_date DATE NOT NULL,
                    expected_checkout_date DATE NULL,
                    actual_checkout_date DATE NULL,
                    status TEXT NOT NULL,
                    payment_status TEXT NOT NULL,
                    total_amount NUMERIC(12,2) NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    hotel_id INTEGER NULL,
                    reservation_number TEXT NULL,
                    deleted_at DATETIME NULL,
                    FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
                    FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE,
                    FOREIGN KEY(manager_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY(hotel_id) REFERENCES hotels(id) ON DELETE SET NULL,
                    UNIQUE(hotel_id, reservation_number)
                );
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO reservations_old (
                    id, client_id, room_id, manager_id, checkin_date,
                    expected_checkout_date, actual_checkout_date, status, payment_status,
                    total_amount, created_at, updated_at, hotel_id, reservation_number, deleted_at
                )
                SELECT
                    id,
                    client_id,
                    room_id,
                    manager_id,
                    COALESCE(checkin_date, expected_checkin_date),
                    expected_checkout_date,
                    actual_checkout_date,
                    status,
                    payment_status,
                    total_amount,
                    created_at,
                    updated_at,
                    hotel_id,
                    reservation_number,
                    deleted_at
                FROM reservations;
            SQL);

            DB::statement('DROP TABLE reservations');
            DB::statement('ALTER TABLE reservations_old RENAME TO reservations');
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('ALTER TABLE reservations MODIFY reservation_number INT UNSIGNED NULL');
            DB::statement('UPDATE reservations SET reservation_number = CAST(reservation_number AS UNSIGNED)');
            DB::statement('ALTER TABLE reservations MODIFY checkin_date DATE NOT NULL');
            DB::statement('UPDATE reservations SET checkin_date = COALESCE(checkin_date, expected_checkin_date)');

            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('expected_checkin_date');
            });
        }
    }
};
