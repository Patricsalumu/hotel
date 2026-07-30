<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE reservations MODIFY checkin_date DATETIME NULL');
            DB::statement('ALTER TABLE reservations MODIFY actual_checkout_date DATETIME NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE reservations ALTER COLUMN checkin_date TYPE timestamp without time zone USING checkin_date::timestamp");
            DB::statement("ALTER TABLE reservations ALTER COLUMN actual_checkout_date TYPE timestamp without time zone USING actual_checkout_date::timestamp");

            return;
        }

        if ($driver === 'sqlite') {
            if (! Schema::hasColumn('reservations', 'discount_amount')) {
                DB::statement('ALTER TABLE reservations RENAME TO reservations_old');

                DB::statement('CREATE TABLE reservations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    client_id INTEGER NOT NULL,
                    apartment_id INTEGER NOT NULL,
                    room_id INTEGER NULL,
                    hotel_id INTEGER NOT NULL,
                    reservation_number VARCHAR(255) NOT NULL,
                    manager_id INTEGER NOT NULL,
                    expected_checkin_date DATE NOT NULL,
                    checkin_date DATETIME NULL,
                    expected_checkout_date DATE NULL,
                    actual_checkout_date DATETIME NULL,
                    status VARCHAR(255) NOT NULL DEFAULT "reserved",
                    payment_status VARCHAR(255) NOT NULL DEFAULT "unpaid",
                    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    discount_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL
                )');

            DB::statement('INSERT INTO reservations (
                id, client_id, apartment_id, room_id, hotel_id, reservation_number, manager_id,
                expected_checkin_date, checkin_date, expected_checkout_date, actual_checkout_date,
                status, payment_status, total_amount, discount_amount, created_at, updated_at, deleted_at
            ) SELECT
                id, client_id, apartment_id, room_id, hotel_id, reservation_number, manager_id,
                expected_checkin_date, checkin_date, expected_checkout_date, actual_checkout_date,
                status, payment_status, total_amount, discount_amount, created_at, updated_at, deleted_at
            FROM reservations_old');

            DB::statement('DROP TABLE reservations_old');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE reservations MODIFY checkin_date DATE NULL');
            DB::statement('ALTER TABLE reservations MODIFY actual_checkout_date DATE NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE reservations ALTER COLUMN checkin_date TYPE date USING checkin_date::date");
            DB::statement("ALTER TABLE reservations ALTER COLUMN actual_checkout_date TYPE date USING actual_checkout_date::date");

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE reservations RENAME TO reservations_old');

            DB::statement('CREATE TABLE reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                apartment_id INTEGER NOT NULL,
                room_id INTEGER NULL,
                hotel_id INTEGER NOT NULL,
                reservation_number VARCHAR(255) NOT NULL,
                manager_id INTEGER NOT NULL,
                expected_checkin_date DATE NOT NULL,
                checkin_date DATE NULL,
                expected_checkout_date DATE NULL,
                actual_checkout_date DATE NULL,
                status VARCHAR(255) NOT NULL DEFAULT "reserved",
                payment_status VARCHAR(255) NOT NULL DEFAULT "unpaid",
                total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                discount_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )');

            DB::statement('INSERT INTO reservations (
                id, client_id, apartment_id, room_id, hotel_id, reservation_number, manager_id,
                expected_checkin_date, checkin_date, expected_checkout_date, actual_checkout_date,
                status, payment_status, total_amount, discount_amount, created_at, updated_at, deleted_at
            ) SELECT
                id, client_id, apartment_id, room_id, hotel_id, reservation_number, manager_id,
                expected_checkin_date, checkin_date, expected_checkout_date, actual_checkout_date,
                status, payment_status, total_amount, discount_amount, created_at, updated_at, deleted_at
            FROM reservations_old');

            DB::statement('DROP TABLE reservations_old');
        }
    }
};
