<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Client;
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

        $clients = Client::orderBy('name')->get();

        return view('dashboard', compact(
            'hotel',
            'occupied',
            'reserved',
            'available',
            'todayIncome',
            'todayExpenses',
            'rooms',
            'clients'
        ));
    }
}
