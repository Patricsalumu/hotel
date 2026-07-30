<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $hotel = $request->user()->currentHotel();

        if (! $hotel) {
            return view('dashboard', ['hotel' => null]);
        }

        $roomQuery = Room::query()->whereHas('apartment', fn ($query) => $query->where('hotel_id', $hotel->id));

        $occupied = (clone $roomQuery)->where('status', 'occupied')->count();
        $reserved = (clone $roomQuery)->where('status', 'reserved')->count();
        $available = (clone $roomQuery)->where('status', 'available')->count();

        $todayIncome = Payment::whereHas('reservation.room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->whereDate('created_at', today())
            ->sum('amount');

        $todayExpenses = Expense::where('hotel_id', $hotel->id)
            ->whereDate('created_at', today())
            ->sum('amount');

        $rooms = $roomQuery
            ->with(['apartment', 'reservations' => function ($query) {
                $query->latest()->with('client');
            }])
            ->orderBy('order_index')
            ->get();

        $availableRooms = Room::query()
            ->where('status', 'available')
            ->whereHas('apartment', fn ($query) => $query->where('hotel_id', $hotel->id))
            ->orderBy('apartment_id')
            ->orderBy('number')
            ->get(['id', 'apartment_id', 'number']);

        $reservations = Reservation::query()
            ->with(['client', 'room'])
            ->where('hotel_id', $hotel->id)
            ->where('status', 'reserved')
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        $dashboardReservations = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'apartment_id' => $reservation->apartment_id,
                'client_name' => $reservation->client->name,
                'expected_checkin_date' => optional($reservation->expected_checkin_date)->format('Y-m-d'),
                'checkin_date' => optional($reservation->checkin_date)->format('Y-m-d'),
                'expected_checkout_date' => optional($reservation->expected_checkout_date)->format('Y-m-d'),
                'status' => $reservation->status,
            ];
        })->values();

        $clients = Client::where('hotel_id', $hotel->id)
            ->orderBy('name')
            ->get();

        $isOwner = $request->user()->isOwner();

        return view('dashboard', compact(
            'hotel',
            'occupied',
            'reserved',
            'available',
            'todayIncome',
            'todayExpenses',
            'rooms',
            'availableRooms',
            'reservations',
            'dashboardReservations',
            'clients',
            'isOwner'
        ));
    }
}
