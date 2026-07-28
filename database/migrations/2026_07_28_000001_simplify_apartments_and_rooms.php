<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            if (Schema::hasTable('apartments')) {
                DB::statement('ALTER TABLE apartments RENAME TO apartments_old');
                Schema::create('apartments', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                    $table->string('name');
                    $table->decimal('price_per_night', 12, 2)->default(0);
                    $table->timestamps();
                });
                DB::statement('INSERT INTO apartments (id, hotel_id, name, price_per_night, created_at, updated_at) SELECT id, hotel_id, name, 0, created_at, updated_at FROM apartments_old');
                DB::statement('DROP TABLE apartments_old');
            } else {
                Schema::create('apartments', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                    $table->string('name');
                    $table->decimal('price_per_night', 12, 2)->default(0);
                    $table->timestamps();
                });
            }

            if (Schema::hasTable('rooms')) {
                DB::statement('ALTER TABLE rooms RENAME TO rooms_old');
                // ensure old unique indexes won't conflict with new table index names
                DB::statement('DROP INDEX IF EXISTS rooms_apartment_id_number_unique');
                Schema::create('rooms', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
                    $table->string('number');
                    $table->enum('status', ['available', 'reserved', 'occupied'])->default('available');
                    $table->unsignedInteger('order_index')->default(0);
                    $table->timestamps();
                    $table->unique(['apartment_id', 'number']);
                });
                DB::statement('INSERT INTO rooms (id, apartment_id, number, status, order_index, created_at, updated_at) SELECT id, apartment_id, number, status, order_index, created_at, updated_at FROM rooms_old');
                DB::statement('DROP TABLE rooms_old');
                // After recreating rooms, some tables (eg. reservations) may still have
                // CREATE TABLE SQL referencing "rooms_old" as a foreign key target.
                // Detect those and rebuild them to reference "rooms" instead.
                $objs = DB::select("SELECT name, sql FROM sqlite_master WHERE sql LIKE '%rooms_old%'");
                foreach ($objs as $obj) {
                    if ($obj->name === 'reservations') {
                        $oldCreate = $obj->sql;
                        $newCreate = str_replace('"rooms_old"', '"rooms"', $oldCreate);
                        // create a new table reservations_new based on modified SQL
                        $newCreate = preg_replace('/CREATE TABLE "reservations"/i', 'CREATE TABLE "reservations_new"', $newCreate, 1);
                        DB::statement($newCreate);
                        // copy data
                        DB::statement('INSERT INTO reservations_new SELECT * FROM reservations');
                        DB::statement('DROP TABLE reservations');
                        DB::statement('ALTER TABLE reservations_new RENAME TO reservations');
                    }
                }
            } else {
                Schema::create('rooms', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
                    $table->string('number');
                    $table->enum('status', ['available', 'reserved', 'occupied'])->default('available');
                    $table->unsignedInteger('order_index')->default(0);
                    $table->timestamps();
                    $table->unique(['apartment_id', 'number']);
                });
            }

            return;
        }

        if (Schema::hasColumn('apartments', 'floor_number')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->dropColumn('floor_number');
            });
        }

        if (! Schema::hasColumn('apartments', 'price_per_night')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->decimal('price_per_night', 12, 2)->default(0)->after('name');
            });
        }

        foreach (['type', 'dimension', 'shape', 'position_x', 'position_y', 'price_per_night'] as $column) {
            if (Schema::hasColumn('rooms', $column)) {
                Schema::table('rooms', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        if (! Schema::hasColumn('rooms', 'status')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->enum('status', ['available', 'reserved', 'occupied'])->default('available')->after('number');
            });
        }

        if (! Schema::hasColumn('rooms', 'order_index')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->unsignedInteger('order_index')->default(0)->after('status');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE apartments RENAME TO apartments_old');
            Schema::create('apartments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('floor_number')->default(0);
                $table->timestamps();
            });
            DB::statement('INSERT INTO apartments (id, hotel_id, name, floor_number, created_at, updated_at) SELECT id, hotel_id, name, 0, created_at, updated_at FROM apartments_old');
            DB::statement('DROP TABLE apartments_old');

            DB::statement('ALTER TABLE rooms RENAME TO rooms_old');
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
            DB::statement('INSERT INTO rooms (id, apartment_id, number, type, price_per_night, dimension, shape, position_x, position_y, status, order_index, created_at, updated_at) SELECT id, apartment_id, number, "simple", 0, NULL, NULL, 0, 0, status, order_index, created_at, updated_at FROM rooms_old');
            DB::statement('DROP TABLE rooms_old');

            return;
        }

        if (Schema::hasColumn('apartments', 'price_per_night')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->dropColumn('price_per_night');
            });
        }

        if (! Schema::hasColumn('apartments', 'floor_number')) {
            Schema::table('apartments', function (Blueprint $table) {
                $table->unsignedInteger('floor_number')->default(0)->after('name');
            });
        }

        foreach (['type', 'dimension', 'shape', 'position_x', 'position_y', 'price_per_night'] as $column) {
            if (! Schema::hasColumn('rooms', $column)) {
                Schema::table('rooms', function (Blueprint $table) use ($column): void {
                    $table->string($column)->nullable();
                });
            }
        }
    }
};
