<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationBillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationBillingService $billingService)
    {
        $this->authorizeResource(Reservation::class, 'reservation');
    }

    public function index(Request $request)
    {
        $hotel = $request->user()->currentHotel();

        $query = Reservation::query()
            ->with(['client', 'room.apartment', 'payments'])
            ->whereHas('room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id));

        $this->applyFilters($query, $request);

        $reservations = $query->latest()->paginate(15)->withQueryString();

        $clients = Client::orderBy('name')->get();
        $availableRooms = Room::where('status', 'available')
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->orderBy('number')
            ->get();

        return view('reservations.index', compact('reservations', 'clients', 'availableRooms', 'hotel'));
    }

    public function store(StoreReservationRequest $request)
    {
        $hotel = $request->user()->currentHotel();
        $room = Room::where('id', $request->integer('room_id'))
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->firstOrFail();

        if ($room->status === 'occupied') {
            return back()->withErrors(['room_id' => 'Cette chambre est déjà occupée et ne peut pas être réservée.'])->withInput();
        }

        $expectedCheckoutDate = $request->date('expected_checkout_date')
            ? Carbon::parse($request->date('expected_checkout_date'))->toDateString()
            : Carbon::parse($request->date('checkin_date'))->toDateString();

        $reservation = Reservation::create([
            'client_id' => $request->integer('client_id'),
            'room_id' => $room->id,
            'manager_id' => $request->user()->id,
            'checkin_date' => $request->date('checkin_date'),
            'expected_checkout_date' => $expectedCheckoutDate,
            'status' => $request->input('status', 'reserved'),
            'payment_status' => 'unpaid',
            'total_amount' => 0,
        ]);

        $reservation->refresh();
        $reservation->update([
            'total_amount' => $this->billingService->computeTotal($reservation, $hotel),
        ]);

        $room->update([
            'status' => $reservation->status === 'checked_in' ? 'occupied' : 'occupied',
        ]);
        $availableRooms = Room::where('status', 'available')
            ->whereHas('apartment', fn($q) =>
                $q->where('hotel_id', $hotel->id)
            )
            ->pluck('number')
            ->toArray();

        $shareText = "📊 RAPPORT RÉSERVATIONS – {$hotel->name}\n\n";
        $shareText .= "📅 Date : " . now()->format('Y-m-d') . "\n\n";

        $shareText .= "🛏 Chambres libres : " . count($availableRooms) . "\n";

        $pageTotalAmount = $reservation->total_amount;
        $pagePaidAmount = $reservation->payments->sum('amount');
        $pageRemainingAmount = max(0, $pageTotalAmount - $pagePaidAmount);
        if (count($availableRooms)) {
            $shareText .= "➡️ " . implode(', ', $availableRooms) . "\n\n";
        }

        $shareText .= "🧾 Réservations : " . $reservation->count() . "\n";
        $shareText .= "💰 Total : " . number_format($pageTotalAmount,2) . "\n";
        $shareText .= "✅ Payé : " . number_format($pagePaidAmount,2) . "\n";
        $shareText .= "❗ Reste : " . number_format($pageRemainingAmount,2) . "\n\n";

        $shareText .= "Gestion via Ayanna ERP";

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Réservation enregistrée avec succès.')
            ->with('share_text', $this->billingService->shareSummary(
                $reservation->fresh('room', 'client'),
                $hotel
            ));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['client', 'room.apartment.hotel', 'payments', 'manager']);
        return view('reservations.show', compact('reservation'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $action = $request->input('action');

        if ($action === 'checkin') {
            $reservation->update(['status' => 'checked_in']);
            $reservation->room->update(['status' => 'occupied']);
        }

        if ($action === 'checkout') {
            $reservation->update([
                'status' => 'checked_out',
                'actual_checkout_date' => today(),
            ]);
            $hotel = $request->user()->currentHotel();
            $reservation->update([
                'total_amount' => $this->billingService->computeTotal($reservation->fresh(), $hotel),
            ]);
            $reservation->room->update(['status' => 'available']);
        }

        return back()->with('success', 'Statut de la réservation mis à jour avec succès.');
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Reservation::class);
        $hotel = $request->user()->currentHotel();
        $query = Reservation::query()
            ->with(['client', 'room', 'payments'])
            ->whereHas('room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->latest();

        $this->applyFilters($query, $request);
        $reservations = $query->get();

        $pdf = Pdf::loadView('pdf.reservations', compact('reservations', 'hotel'));
        return $pdf->download('reservations-report.pdf');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('checkin_date', [$request->date('from_date'), $request->date('to_date')]);
        } else {
            $query->whereDate('checkin_date', today());
        }

        if ($request->filled('room_number')) {
            $roomNumber = $request->string('room_number')->toString();
            $query->whereHas('room', fn ($q) => $q->where('number', 'like', "%{$roomNumber}%"));
        }

        if ($request->filled('client_name')) {
            $clientName = $request->string('client_name')->toString();
            $query->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$clientName}%"));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('nights')) {
            $nights = (int) $request->input('nights');
            $query->whereRaw('DATEDIFF(COALESCE(actual_checkout_date, expected_checkout_date, CURDATE()), checkin_date) = ?', [$nights]);
        }
    }
}
