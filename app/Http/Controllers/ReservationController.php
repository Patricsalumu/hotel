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

        $clients = Client::where('hotel_id', $hotel->id)
            ->orderBy('name')
            ->get();
        $availableRooms = Room::where('status', 'available')
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->orderBy('number')
            ->get();

        // build auxiliary data for sharing
        $availableNumbers = $availableRooms->pluck('number')->toArray();

        $occupiedRooms = Room::where('status', 'occupied')
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->orderBy('number')
            ->get();
        $occupiedNumbers = $occupiedRooms->pluck('number')->toArray();

        // total received today for the filtered reservations (payments)
        $reservationsForTotals = $reservations->getCollection();
        $todayReceived = $reservationsForTotals->sum(fn ($r) => $r->payments->sum('amount'));

        // expenses today
        $todayExpenses = \App\Models\Expense::where('hotel_id', $hotel->id)
            ->whereDate('created_at', today())
            ->sum('amount');
        $balance = $todayReceived - $todayExpenses;

        $latestOccupiedReservation = Reservation::query()
            ->with('room.apartment')
            ->where('status', 'checked_in')
            ->whereHas('room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->latest('updated_at')
            ->first();

        $latestOccupiedLine = $latestOccupiedReservation
            ? "Dernière chambre occupée : " . $latestOccupiedReservation->room->number . " à " . $latestOccupiedReservation->updated_at?->format('H:i')
            : "Dernière chambre occupée : Aucune";

        $sharePageText = $latestOccupiedLine . "\n" .
            "Chambres occupées : " . (count($occupiedNumbers) ? implode(', ', $occupiedNumbers) : 'aucune') . "\n" .
            "Chambres libres : " . (count($availableNumbers) ? implode(', ', $availableNumbers) : 'aucune') . "\n" .
            "Total Entrées du Jour : " . number_format($todayReceived, 2) . "\n" .
            "Total Sorties du Jour : " . number_format($todayExpenses, 2) . "\n" .
            "Solde : " . number_format($balance, 2);

        $sharePageMessage = "📢 *Notification {$hotel->name}*\n\n";
        $sharePageMessage .= $latestOccupiedLine . "\n\n";
        $sharePageMessage .= "*Chambres occupées*\n" . (count($occupiedNumbers) ? implode(', ', $occupiedNumbers) : 'Aucune') . "\n\n";
        $sharePageMessage .= "*Chambres libres*\n" . (count($availableNumbers) ? implode(', ', $availableNumbers) : 'Aucune') . "\n\n";
        $sharePageMessage .= "Total Entrées du Jour : " . number_format($todayReceived, 2) . "\n";
        $sharePageMessage .= "Total Sorties du Jour : " . number_format($todayExpenses, 2) . "\n";
        $sharePageMessage .= "Solde : " . number_format($balance, 2) . "\n\n";
        $sharePageMessage .= "— Informatisée par Ayanna ERP";

        $whatsAppPhone = preg_replace('/\D+/', '', (string) $hotel->phone);
        // prepare calendar-friendly reservations data to avoid Blade parsing issues
        $calendarReservations = $reservations->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'room_number' => $r->room->number,
            'client_name' => $r->client->name,
            'checkin' => $r->checkin_date?->format('Y-m-d'),
            'checkout' => $r->expected_checkout_date?->format('Y-m-d'),
            'status' => $r->status,
        ])->values()->toArray();

        return view('reservations.index', compact(
            'reservations',
            'clients',
            'availableRooms',
            'hotel',
            'sharePageText',
            'sharePageMessage',
            'whatsAppPhone',
            'availableNumbers',
            'occupiedNumbers',
            'todayReceived',
            'todayExpenses',
            'balance',
            'calendarReservations'
        ));
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

        // determine initial reservation status based on checkin date relative to today
        $checkinDate = Carbon::parse($request->date('checkin_date'))->toDateString();
        $today = Carbon::today()->toDateString();

        if ($checkinDate === $today) {
            // checkin happening today -> mark occupied
            $initialStatus = 'checked_in';
            $roomStatus = 'occupied';
        } else {
            // past or future check‑in should simply be reserved until the day arrives
            $initialStatus = 'reserved';
            $roomStatus = 'reserved';
        }

        $client = Client::where('id', $request->integer('client_id'))
            ->where('hotel_id', $hotel->id)
            ->firstOrFail();

        $reservation = Reservation::create([
            'client_id' => $client->id,
            'room_id' => $room->id,
            'manager_id' => $request->user()->id,
            'id_user' => $request->user()->id,
            'checkin_date' => $request->date('checkin_date'),
            'expected_checkout_date' => $expectedCheckoutDate,
            'status' => $initialStatus,
            'payment_status' => 'unpaid',
            'total_amount' => 0,
        ]);

        $reservation->refresh();
        $reservation->update([
            'total_amount' => $this->billingService->computeTotal($reservation, $hotel),
        ]);

        $room->update([
            'status' => $roomStatus,
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
            ->with('success', 'Réservation enregistrée avec succès.');
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['client', 'room.apartment.hotel', 'payments.user', 'manager', 'user']);
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

    public function invoicePdf(Request $request, Reservation $reservation)
    {
        $this->authorize('view', $reservation);

        $reservation->load([
            'client',
            'room.apartment.hotel',
            'payments.user',
            'user',
            'manager',
        ]);

        $hotel = $reservation->room->apartment->hotel;
        $paper = $request->query('paper', 'a4') === '80mm' ? '80mm' : 'a4';

        $paidAmount = (float) $reservation->payments->sum('amount');
        $totalAmount = (float) $reservation->total_amount;
        $remainingAmount = max(0, $totalAmount - $paidAmount);

        $expectedNights = $reservation->expected_checkout_date
            ? max(1, $reservation->checkin_date->diffInDays($reservation->expected_checkout_date))
            : 1;

        $actualNights = $reservation->computeNights(now(), $hotel->checkout_time);
        $pricePerNight = $actualNights > 0 ? round($totalAmount / $actualNights, 2) : $totalAmount;

        $paymentStatusLabel = [
            'paid' => 'PAYÉ',
            'partial' => 'PARTIEL',
            'unpaid' => 'NON PAYÉ',
        ][$reservation->payment_status] ?? strtoupper($reservation->payment_status);

        $logoDataUri = null;
        if (!empty($hotel->image)) {
            $logoPath = storage_path('app/public/' . $hotel->image);
            if (is_file($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('pdf.invoice', compact(
            'reservation',
            'hotel',
            'paper',
            'paidAmount',
            'totalAmount',
            'remainingAmount',
            'expectedNights',
            'actualNights',
            'pricePerNight',
            'paymentStatusLabel',
            'logoDataUri'
        ));

        if ($paper === '80mm') {
            $pdf->setPaper([0, 0, 226.77, 900], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf->download('facture-reservation-' . $reservation->id . '-' . $paper . '.pdf');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('checkin_date', [$request->date('from_date'), $request->date('to_date')]);
        } else {
            $query->where(function ($subQuery) {
                $subQuery->whereDate('checkin_date', today())
                    ->orWhere('status', 'reserved');
            });
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
