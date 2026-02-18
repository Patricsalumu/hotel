<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Client;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationStatusTest extends TestCase
{
    use RefreshDatabase;

    private function prepareEnvironment(): User
    {
        // create an owner user and hotel hierarchy
        $user = User::factory()->create(['role' => 'owner']);
        $hotel = Hotel::create([
            'owner_id' => $user->id,
            'name' => 'Test Hotel',
        ]);
        // create one apartment and room
        $apt = Apartment::create([
            'hotel_id' => $hotel->id,
            'name' => 'Block A',
        ]);
        Room::create([
            'apartment_id' => $apt->id,
            'number' => '101',
            'price_per_night' => 50,
        ]);

        $user->refresh();
        return $user;
    }

    public function test_reservation_with_checkin_today_marks_room_occupied(): void
    {
        $user = $this->prepareEnvironment();
        $room = Room::first();
        $client = Client::create(['name' => 'Sample']);

        $response = $this->actingAs($user)
            ->post(route('reservations.store'), [
                'client_id' => $client->id,
                'room_id' => $room->id,
                'checkin_date' => Carbon::today()->toDateString(),
            ]);

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'status' => 'checked_in',
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'occupied',
        ]);
    }

    public function test_reservation_with_checkin_in_future_marks_room_reserved(): void
    {
        $user = $this->prepareEnvironment();
        $room = Room::first();
        $client = Client::create(['name' => 'Sample']);

        $response = $this->actingAs($user)
            ->post(route('reservations.store'), [
                'client_id' => $client->id,
                'room_id' => $room->id,
                'checkin_date' => Carbon::today()->addDays(2)->toDateString(),
            ]);

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'status' => 'reserved',
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'reserved',
        ]);
    }

    public function test_reservation_with_checkin_in_past_remains_reserved(): void
    {
        $user = $this->prepareEnvironment();
        $room = Room::first();
        $client = Client::create(['name' => 'Sample']);

        $response = $this->actingAs($user)
            ->post(route('reservations.store'), [
                'client_id' => $client->id,
                'room_id' => $room->id,
                'checkin_date' => Carbon::today()->subDays(3)->toDateString(),
            ]);

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'status' => 'reserved',
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'reserved',
        ]);
    }
}
