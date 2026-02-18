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
            $table->foreignId('id_user')->nullable()->after('manager_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('id_user')->nullable()->after('reservation_id')->constrained('users')->nullOnDelete();
        });

        DB::table('reservations')->whereNull('id_user')->update([
            'id_user' => DB::raw('manager_id'),
        ]);

        DB::table('payments as p')
            ->join('reservations as r', 'r.id', '=', 'p.reservation_id')
            ->whereNull('p.id_user')
            ->update([
                'p.id_user' => DB::raw('r.manager_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_user');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_user');
        });
    }
};
