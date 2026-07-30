<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_search_returns_only_clients_from_current_hotel(): void
    {
        $hotel = Hotel::create(['name' => 'Test Hotel', 'owner_id' => User::factory()->create(['role' => 'owner'])->id]);
        $user = User::factory()->create(['role' => 'manager', 'hotel_id' => $hotel->id]);

        Client::create(['hotel_id' => $hotel->id, 'name' => 'Jean Dupont', 'phone' => '123']);
        Client::create(['hotel_id' => $hotel->id, 'name' => 'Alice Martin', 'phone' => '456']);

        $otherHotel = Hotel::create(['name' => 'Other Hotel', 'owner_id' => User::factory()->create(['role' => 'owner'])->id]);
        Client::create(['hotel_id' => $otherHotel->id, 'name' => 'Jean Other', 'phone' => '789']);

        $response = $this->actingAs($user)
            ->getJson(route('clients.search', ['q' => 'Jean']));

        $response->assertOk();
        $response->assertJsonCount(1, 'clients');
        $response->assertJsonPath('clients.0.name', 'Jean Dupont');
    }

    public function test_quick_store_creates_a_client_for_current_hotel(): void
    {
        $hotel = Hotel::create(['name' => 'Test Hotel', 'owner_id' => User::factory()->create(['role' => 'owner'])->id]);
        $user = User::factory()->create(['role' => 'manager', 'hotel_id' => $hotel->id]);

        $response = $this->actingAs($user)
            ->postJson(route('clients.quick-store'), [
                'name' => 'Nouveau client',
                'phone' => '987654321',
                'email' => 'client@example.com',
                'nationality' => 'Congolaise',
                'document_number' => 'ABC-123',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('client.name', 'Nouveau client');
        $this->assertDatabaseHas('clients', [
            'name' => 'Nouveau client',
            'hotel_id' => $hotel->id,
        ]);
    }
}
