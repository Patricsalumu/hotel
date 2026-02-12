<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

class ReservationBillingService
{
    public function calculateNights(Reservation $reservation, Hotel $hotel, ?Carbon $now = null): int
    {
        $now = $now ?? now();
        return $reservation->computeNights($now, $hotel->checkout_time);
    }

    public function computeTotal(Reservation $reservation, Hotel $hotel, ?Carbon $now = null): float
    {
        $reservation->loadMissing('room');
        $nights = $this->calculateNights($reservation, $hotel, $now);

        return (float) $reservation->room->price_per_night * $nights;
    }

    public function refreshPaymentStatus(Reservation $reservation): void
    {
        $paid = (float) $reservation->payments()->sum('amount');
        $total = (float) $reservation->total_amount;

        if ($paid <= 0) {
            $status = 'unpaid';
        } elseif ($paid >= $total) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $reservation->update(['payment_status' => $status]);
    }

    public function shareSummary(Reservation $reservation, Hotel $hotel): string
    {
        $reservation->loadMissing('room', 'client');
        $occupiedRooms = $hotel->apartments()->withCount(['rooms as occupied_rooms_count' => function ($query) {
            $query->where('status', 'occupied');
        }, 'rooms as available_rooms_count' => function ($query) {
            $query->where('status', 'available');
        }])->get()->sum('occupied_rooms_count');

        $availableRooms = $hotel->apartments()->withCount(['rooms as available_rooms_count' => function ($query) {
            $query->where('status', 'available');
        }])->get()->sum('available_rooms_count');

        return "Chambre {$reservation->room->number} occupée. Checkout prévu: "
            . optional($reservation->expected_checkout_date)->format('Y-m-d')
            . ". Montant total: {$reservation->total_amount}. Chambres occupées: {$occupiedRooms}, libres: {$availableRooms}.";
    }
}
