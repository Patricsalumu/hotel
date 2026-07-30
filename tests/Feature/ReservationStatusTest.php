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
            'price_per_night' => 50,
        ]);
        Room::create([
            'apartment_id' => $apt->id,
            'number' => '101',
        ]);

        $user->refresh();
        return $user;
    }

    public function test_reservation_can_be_created_from_apartment_and_assigned_on_checkin(): void
    {
        $user = $this->prepareEnvironment();
        $apartment = Apartment::first();
        $room = Room::first();
        $client = Client::create(['name' => 'Sample', 'hotel_id' => $user->currentHotel()?->id]);

        $response = $this->actingAs($user)
            ->post(route('reservations.store'), [
                'client_id' => $client->id,
                'apartment_id' => $apartment->id,
                'checkin_date' => Carbon::today()->toDateString(),
                'expected_checkout_date' => Carbon::today()->addDays(2)->toDateString(),
            ]);

        $response->assertRedirect(route('reservations.index'));

        $reservation = Reservation::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'apartment_id' => $apartment->id,
            'room_id' => null,
            'status' => 'reserved',
        ]);

        $checkinResponse = $this->actingAs($user)
            ->put(route('reservations.update', $reservation), [
                'action' => 'checkin',
            ]);

        $checkinResponse->assertRedirect();

        $reservation->refresh();
        $this->assertSame('checked_in', $reservation->status);
        $this->assertSame($room->id, $reservation->room_id);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'occupied',
        ]);
    }

    public function test_reservation_with_checkin_in_future_marks_room_reserved(): void
    {
        $user = $this->prepareEnvironment();
        $room = Room::first();
        $client = Client::create(['name' => 'Sample', 'hotel_id' => $user->currentHotel()?->id]);

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
        $client = Client::create(['name' => 'Sample', 'hotel_id' => $user->currentHotel()?->id]);

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

    public function test_checkin_and_checkout_dates_are_saved_with_time(): void
    {
        $user = $this->prepareEnvironment();
        $room = Room::first();
        $client = Client::create(['name' => 'Sample', 'hotel_id' => $user->currentHotel()?->id]);

        $response = $this->actingAs($user)
            ->post(route('reservations.store'), [
                'client_id' => $client->id,
                'room_id' => $room->id,
                'checkin_date' => '2026-07-29 14:35:00',
                'expected_checkout_date' => '2026-07-30',
            ]);

        $response->assertRedirect(route('reservations.index'));

        $reservation = Reservation::query()->latest('id')->firstOrFail();

        $this->assertSame('14:35', $reservation->checkin_date->format('H:i'));

        $checkoutResponse = $this->actingAs($user)
            ->put(route('reservations.update', $reservation), [
                'action' => 'checkout',
            ]);

        $checkoutResponse->assertRedirect();

        $reservation->refresh();

        $this->assertNotSame('00:00', $reservation->actual_checkout_date->format('H:i'));
    }

    public function test_checkout_releases_room_even_if_expected_checkout_date_not_reached(): void
    {
        $user = $this->prepareEnvironment();
        $hotel = $user->currentHotel();
        $room = Room::first();
        $client = Client::create([
            'name' => 'Sample',
            'hotel_id' => $hotel?->id,
        ]);

        $createResponse = $this->actingAs($user)
            ->post(route('reservations.store'), [
                'client_id' => $client->id,
                'room_id' => $room->id,
                'checkin_date' => Carbon::today()->toDateString(),
                'expected_checkout_date' => Carbon::today()->addDays(5)->toDateString(),
            ]);

        $createResponse->assertRedirect(route('reservations.index'));

        $reservation = Reservation::query()->latest('id')->firstOrFail();

        $checkoutResponse = $this->actingAs($user)
            ->put(route('reservations.update', $reservation), [
                'action' => 'checkout',
            ]);

        $checkoutResponse->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_out',
            'actual_checkout_date' => Carbon::today()->toDateString(),
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'available',
        ]);
    }
}
