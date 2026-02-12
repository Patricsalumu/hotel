<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HotelAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@hotel.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'hotel_id' => null,
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@hotel.test'],
            [
                'name' => 'Owner Hotel',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );

        $hotel = Hotel::firstOrCreate(
            ['owner_id' => $owner->id],
            [
                'name' => 'Hotel Démo',
                'address' => 'Avenue Centrale',
                'city' => 'Kinshasa',
                'phone' => '+243000000000',
                'checkout_time' => '12:00',
            ]
        );

        $owner->update(['hotel_id' => $hotel->id]);

        $manager = User::firstOrCreate(
            ['email' => 'manager@hotel.test'],
            [
                'name' => 'Manager Hotel',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'hotel_id' => $hotel->id,
            ]
        );

        $apartmentA = Apartment::firstOrCreate([
            'hotel_id' => $hotel->id,
            'name' => 'Bloc A',
        ], [
            'floor_number' => 1,
        ]);

        $apartmentB = Apartment::firstOrCreate([
            'hotel_id' => $hotel->id,
            'name' => 'Bloc B',
        ], [
            'floor_number' => 2,
        ]);

        $room1 = Room::firstOrCreate([
            'apartment_id' => $apartmentA->id,
            'number' => 'A101',
        ], [
            'type' => 'simple',
            'price_per_night' => 40,
            'status' => 'occupied',
            'order_index' => 1,
        ]);

        $room2 = Room::firstOrCreate([
            'apartment_id' => $apartmentA->id,
            'number' => 'A102',
        ], [
            'type' => 'double',
            'price_per_night' => 60,
            'status' => 'available',
            'order_index' => 2,
        ]);

        Room::firstOrCreate([
            'apartment_id' => $apartmentB->id,
            'number' => 'B201',
        ], [
            'type' => 'suite',
            'price_per_night' => 90,
            'status' => 'reserved',
            'order_index' => 3,
        ]);

        $client = Client::firstOrCreate([
            'name' => 'Client Démo',
            'phone' => '+243100200300',
        ], [
            'email' => 'client@demo.test',
            'nationality' => 'Congolaise',
            'document_number' => 'DOC-1234',
        ]);

        $reservation = Reservation::firstOrCreate([
            'client_id' => $client->id,
            'room_id' => $room1->id,
            'manager_id' => $manager->id,
            'checkin_date' => now()->toDateString(),
        ], [
            'expected_checkout_date' => now()->addDay()->toDateString(),
            'status' => 'checked_in',
            'payment_status' => 'partial',
            'total_amount' => 80,
        ]);

        Payment::firstOrCreate([
            'reservation_id' => $reservation->id,
            'amount' => 40,
            'payment_method' => 'cash',
            'created_at' => now(),
        ]);

        Expense::firstOrCreate([
            'hotel_id' => $hotel->id,
            'user_id' => $manager->id,
            'category' => 'transport',
            'amount' => 15,
            'created_at' => now(),
        ], [
            'description' => 'Course ravitaillement',
        ]);
    }
}
