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
        Schema::create('expense_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'name']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('user_id')->constrained('expense_accounts')->nullOnDelete();
        });

        $hotels = DB::table('hotels')->select('id')->get();

        foreach ($hotels as $hotel) {
            $accountId = DB::table('expense_accounts')->insertGetId([
                'hotel_id' => $hotel->id,
                'name' => 'Autres',
                'description' => 'Compte par défaut',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('expenses')
                ->where('hotel_id', $hotel->id)
                ->whereNull('account_id')
                ->update(['account_id' => $accountId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::dropIfExists('expense_accounts');
    }
};
