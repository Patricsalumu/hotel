<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationBillingService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationBillingService $billingService)
    {
        $this->authorizeResource(Reservation::class, 'reservation');
    }

    public function index(Request $request)
    {
        $hotel = $request->user()->currentHotel();
        $currency = $hotel->currency ?? 'FC';

        $query = Reservation::query()
            ->withTrashed()
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

        $bookableRooms = Room::whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->orderBy('id')
            ->get();

        // build auxiliary data for sharing
        $availableNumbers = $availableRooms->pluck('number')->toArray();

        $occupiedRooms = Room::where('status', 'occupied')
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->orderBy('number')
            ->get();
        $occupiedNumbers = $occupiedRooms->pluck('number')->toArray();

        // total received for active (non-cancelled) reservations in current page/filter
        $reservationsForTotals = $reservations->getCollection()->filter(fn ($r) => ! $r->trashed());
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
            ? "Chambre : " . $latestOccupiedReservation->room->number . " vient d'etre occupée à " . $latestOccupiedReservation->updated_at?->format('H:i')
            : "Dernière chambre occupée : Aucune";

        $sharePageText = $latestOccupiedLine . "\n" .
            "Chambres occupées : " . (count($occupiedNumbers) ? implode(', ', $occupiedNumbers) : 'aucune') . "\n" .
            "Chambres libres : " . (count($availableNumbers) ? implode(', ', $availableNumbers) : 'aucune') . "\n" .
            "Total Entrées du Jour : " . Money::format($todayReceived, $currency) . "\n" .
            "Total Sorties du Jour : " . Money::format($todayExpenses, $currency) . "\n" .
            "Solde : " . Money::format($balance, $currency);

        $sharePageMessage = "📢 *Notification {$hotel->name}*\n\n";
        $sharePageMessage .= $latestOccupiedLine . "\n\n";
        $sharePageMessage .= "*Chambres occupées*\n" . (count($occupiedNumbers) ? implode(', ', $occupiedNumbers) : 'Aucune') . "\n\n";
        $sharePageMessage .= "*Chambres libres*\n" . (count($availableNumbers) ? implode(', ', $availableNumbers) : 'Aucune') . "\n\n";
        $sharePageMessage .= "Total Entrées du Jour : " . Money::format($todayReceived, $currency) . "\n";
        $sharePageMessage .= "Total Sorties du Jour : " . Money::format($todayExpenses, $currency) . "\n";
        $sharePageMessage .= "Solde : " . Money::format($balance, $currency) . "\n\n";
        $sharePageMessage .= "— Informatisée par Ayanna ERP";

        $whatsAppPhone = preg_replace('/\D+/', '', (string) $hotel->phone);
        // prepare calendar-friendly reservations data to avoid Blade parsing issues
        $calendarReservations = $reservations->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'room_id' => $r->room_id,
            'reference' => $r->reference,
            'room_number' => $r->room->number,
            'client_name' => $r->client->name,
            'checkin' => $r->checkin_date?->format('Y-m-d'),
            'checkout' => ($r->actual_checkout_date ?? $r->expected_checkout_date ?? now())->format('Y-m-d'),
            'status' => $r->status,
            'is_cancelled' => $r->trashed(),
        ])->values()->toArray();

        $roomPlanningReservations = Reservation::query()
            ->select(['id', 'room_id', 'checkin_date', 'expected_checkout_date', 'status'])
            ->where('hotel_id', $hotel->id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['reserved', 'checked_in'])
            ->orderBy('checkin_date')
            ->get()
            ->map(fn ($reservation) => [
                'id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'checkin' => $reservation->checkin_date?->format('Y-m-d'),
                'expected_checkout' => $reservation->expected_checkout_date?->format('Y-m-d'),
                'status' => $reservation->status,
            ])
            ->values()
            ->toArray();

        return view('reservations.index', compact(
            'reservations',
            'clients',
            'availableRooms',
            'bookableRooms',
            'hotel',
            'sharePageText',
            'sharePageMessage',
            'whatsAppPhone',
            'availableNumbers',
            'occupiedNumbers',
            'todayReceived',
            'todayExpenses',
            'balance',
            'calendarReservations',
            'roomPlanningReservations'
        ));
    }

    public function store(StoreReservationRequest $request)
    {
        $hotel = $request->user()->currentHotel();
        $checkinDate = Carbon::parse($request->date('checkin_date'))->startOfDay();
        $expectedCheckoutDate = $request->date('expected_checkout_date')
            ? Carbon::parse($request->date('expected_checkout_date'))->startOfDay()
            : null;

        $room = Room::where('id', $request->integer('room_id'))
            ->whereHas('apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->firstOrFail();

        // determine initial reservation status based on checkin date relative to today
        $today = Carbon::today()->toDateString();
        $checkinDateString = $checkinDate->toDateString();

        if ($checkinDateString === $today && $room->status !== 'occupied') {
            // checkin happening today -> mark occupied
            $initialStatus = 'checked_in';
            $roomStatus = 'occupied';
        } else {
            // past or future check‑in should simply be reserved until the day arrives
            $initialStatus = 'reserved';
            $roomStatus = $room->status === 'occupied' ? 'occupied' : 'reserved';
        }

        $client = Client::where('id', $request->integer('client_id'))
            ->where('hotel_id', $hotel->id)
            ->firstOrFail();

        $reservation = DB::transaction(function () use ($hotel, $room, $request, $client, $expectedCheckoutDate, $checkinDate, $initialStatus, $roomStatus) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();

            $activeReservations = Reservation::query()
                ->where('room_id', $lockedRoom->id)
                ->where('hotel_id', $hotel->id)
                ->whereNull('deleted_at')
                ->whereIn('status', ['reserved', 'checked_in'])
                ->orderBy('checkin_date')
                ->lockForUpdate()
                ->get();

            $this->assertRoomCanBeScheduled($lockedRoom, $activeReservations, $checkinDate, $expectedCheckoutDate);

            $nextReservationNumber = (int) Reservation::withTrashed()
                ->where('hotel_id', $hotel->id)
                ->lockForUpdate()
                ->max('reservation_number') + 1;

            $reservation = Reservation::create([
                'client_id' => $client->id,
                'room_id' => $lockedRoom->id,
                'hotel_id' => $hotel->id,
                'reservation_number' => $nextReservationNumber,
                'manager_id' => $request->user()->id,
                'id_user' => $request->user()->id,
                'checkin_date' => $checkinDate->toDateString(),
                'expected_checkout_date' => $expectedCheckoutDate?->toDateString(),
                'status' => $initialStatus,
                'payment_status' => 'unpaid',
                'total_amount' => 0,
                'discount_amount' => (float) $request->input('discount_amount', 0),
            ]);

            $reservation->refresh();
            $reservation->update([
                'total_amount' => $this->billingService->computeTotal($reservation, $hotel),
            ]);

            $lockedRoom->update([
                'status' => $roomStatus,
            ]);

            return $reservation->load('payments');
        });
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

        $creationSource = (string) $request->input('creation_source', 'reservations_index');
        $successMessage = $creationSource === 'dashboard_shortcut'
            ? 'Réservation enregistrée avec succès (depuis le raccourci du tableau de bord).'
            : 'Réservation enregistrée avec succès.';

        return redirect()
            ->route('reservations.index')
            ->with('success', $successMessage);
    }

    private function assertRoomCanBeScheduled(Room $room, Collection $activeReservations, Carbon $newCheckin, ?Carbon $newExpectedCheckout): void
    {
        $today = Carbon::today()->startOfDay();

        $occupiedReservation = $activeReservations
            ->filter(fn (Reservation $reservation) => $reservation->status === 'checked_in')
            ->sortByDesc(fn (Reservation $reservation) => $reservation->checkin_date?->timestamp ?? 0)
            ->first();

        if (! $occupiedReservation) {
            $occupiedReservation = $activeReservations
                ->filter(function (Reservation $reservation) use ($today): bool {
                    if ($reservation->status !== 'reserved' || ! $reservation->checkin_date) {
                        return false;
                    }

                    return $reservation->checkin_date->copy()->startOfDay()->lte($today);
                })
                ->sortByDesc(fn (Reservation $reservation) => $reservation->checkin_date?->timestamp ?? 0)
                ->first();
        }

        if ($room->status === 'occupied') {
            if (! $occupiedReservation) {
                throw ValidationException::withMessages([
                    'room_id' => 'Cette chambre est occupée et ne peut pas être planifiée pour le moment.',
                ]);
            }

            if (! $occupiedReservation->expected_checkout_date) {
                throw ValidationException::withMessages([
                    'room_id' => 'Cette chambre est occupée. Indiquez d\'abord une date de départ prévue sur l\'occupation en cours.',
                ]);
            }

            $occupiedExpectedCheckout = $occupiedReservation->expected_checkout_date->copy()->startOfDay();
            if ($newCheckin->lt($occupiedExpectedCheckout)) {
                throw ValidationException::withMessages([
                    'checkin_date' => 'La nouvelle réservation doit commencer à partir de la date de départ prévue de l\'occupation en cours.',
                ]);
            }
        }

        $futureReservedStarts = $activeReservations
            ->filter(fn (Reservation $reservation) => $reservation->status === 'reserved' && $reservation->checkin_date)
            ->map(fn (Reservation $reservation) => $reservation->checkin_date->copy()->startOfDay())
            ->filter(fn (Carbon $checkinDate) => $checkinDate->gt($newCheckin));

        if ($futureReservedStarts->isNotEmpty()) {
            $nearestStart = $futureReservedStarts->sort()->first();
            if (! $newExpectedCheckout) {
                throw ValidationException::withMessages([
                    'expected_checkout_date' => 'La date prévue de sortie est obligatoire pour réserver avant une réservation déjà planifiée.',
                ]);
            }

            if ($newExpectedCheckout->gte($nearestStart)) {
                throw ValidationException::withMessages([
                    'expected_checkout_date' => 'La date prévue de sortie doit être strictement inférieure à la prochaine date d\'arrivée déjà réservée.',
                ]);
            }
        }

        $blockingReservation = $activeReservations->first(function (Reservation $reservation) use ($newCheckin, $occupiedReservation): bool {
            if (! $reservation->checkin_date) {
                return false;
            }

            $start = $reservation->checkin_date->copy()->startOfDay();
            if ($start->gt($newCheckin)) {
                return false;
            }

            if ($reservation->status === 'checked_in') {
                $end = $reservation->expected_checkout_date?->copy()->startOfDay();

                if ($occupiedReservation && $reservation->id === $occupiedReservation->id && $end && $newCheckin->gte($end)) {
                    return false;
                }

                return ! $end || $newCheckin->lt($end);
            }

            if ($reservation->status === 'reserved') {
                $end = $reservation->expected_checkout_date?->copy()->startOfDay();
                return ! $end || $newCheckin->lt($end);
            }

            return false;
        });

        if ($blockingReservation) {
            throw ValidationException::withMessages([
                'checkin_date' => 'Cette date d\'arrivée chevauche une réservation/occupation existante pour cette chambre.',
            ]);
        }
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['client', 'room.apartment.hotel', 'payments.user', 'manager', 'user']);

        $hotel = $reservation->room->apartment->hotel;
        $currency = $hotel->currency ?? 'FC';
        $nights = $reservation->computeNights(now(), $hotel->checkout_time);
        $grossAmount = (float) $reservation->room->price_per_night * $nights;
        $discountAmount = (float) ($reservation->discount_amount ?? 0);
        $netAmount = (float) $reservation->total_amount;
        $paidAmount = (float) $reservation->payments->sum('amount');
        $remainingAmount = max(0, $netAmount - $paidAmount);
        $clientPhone = preg_replace('/\D+/', '', (string) $reservation->client->phone);
        $publicInvoiceA4 = URL::temporarySignedRoute(
            'reservations.public.invoice.pdf',
            now()->addDays(7),
            ['reservation' => $reservation->id, 'paper' => 'a4']
        );

        $waText = "Notification ({$hotel->name})\n";
        $waText .= "Client: {$reservation->client->name}\n";
        $waText .= "Reservation #" . ($reservation->reservation_number ?? $reservation->id) . " - Chambre {$reservation->room->number}\n";
        $waText .= "Nuitees: {$nights}\n";
        $waText .= "Total: " . Money::format($grossAmount, $currency) . "\n";
        $waText .= "Reduction: " . Money::format($discountAmount, $currency) . "\n";
        $waText .= "Net a payer: " . Money::format($netAmount, $currency) . "\n";
        $waText .= "Paye: " . Money::format($paidAmount, $currency) . "\n";
        $waText .= "Reste: " . Money::format($remainingAmount, $currency) . "\n";
        $waText .= "Facture A4: {$publicInvoiceA4}";

        $whatsAppInvoiceUrl = $clientPhone
            ? 'https://wa.me/' . $clientPhone . '?text=' . urlencode($waText)
            : 'https://wa.me/?text=' . urlencode($waText);

        return view('reservations.show', compact('reservation', 'whatsAppInvoiceUrl'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $action = $request->input('action');

        if ($reservation->trashed()) {
            return back()->withErrors(['reservation' => 'Cette réservation est déjà annulée.']);
        }

        if ($action === 'cancel') {
            $reservation->delete();
            $reservation->room->update(['status' => 'available']);

            return redirect()->route('reservations.index')->with('success', 'Réservation annulée avec succès.');
        }

        if ($action === 'checkin') {
            $reservation->update(['status' => 'checked_in']);
            $reservation->room->update(['status' => 'occupied']);
        }

        if ($action === 'checkout') {
            DB::transaction(function () use ($request, $reservation): void {
                $reservation->loadMissing('room.apartment.hotel');

                $reservation->update([
                    'status' => 'checked_out',
                    'actual_checkout_date' => today(),
                ]);

                $reservation->room()->update(['status' => 'available']);

                $hotel = $request->user()->currentHotel() ?? $reservation->room->apartment->hotel;
                if ($hotel) {
                    $reservation->update([
                        'total_amount' => $this->billingService->computeTotal($reservation->fresh(), $hotel),
                    ]);
                }
            });
        }

        return back()->with('success', 'Statut de la réservation mis à jour avec succès.');
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Reservation::class);
        $hotel = $request->user()->currentHotel();
        $hotel->loadMissing('owner');

        $query = Reservation::query()
            ->withTrashed()
            ->with(['client', 'room', 'payments'])
            ->whereHas('room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->latest();

        $this->applyFilters($query, $request);
        $query->whereNull('deleted_at');
        $reservations = $query->get();

        $enterpriseEmail = $hotel->owner?->email;
        $enterpriseAddress = trim(($hotel->address ?? '') . ' ' . ($hotel->city ?? ''));
        $currency = $hotel->currency ?? 'FC';
        $logoDataUri = null;

        if (!empty($hotel->image)) {
            $logoPath = storage_path('app/public/' . $hotel->image);
            if (is_file($logoPath)) {
                $mime = $this->detectMimeType($logoPath);
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('pdf.reservations', compact(
            'reservations',
            'hotel',
            'currency',
            'enterpriseEmail',
            'enterpriseAddress',
            'logoDataUri'
        ));
        return $pdf->download('reservations-report.pdf');
    }

    public function invoicePdf(Request $request, Reservation $reservation)
    {
        $this->authorize('view', $reservation);

        $paper = $request->query('paper', 'a4') === '80mm' ? '80mm' : 'a4';
        [, $pdf] = $this->buildInvoicePdf($reservation, $paper);

        return $pdf->download('facture-reservation-' . ($reservation->reservation_number ?? $reservation->id) . '-' . $paper . '.pdf');
    }

    public function publicInvoicePdf(Request $request, Reservation $reservation)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $paper = $request->query('paper', 'a4') === '80mm' ? '80mm' : 'a4';
        [, $pdf] = $this->buildInvoicePdf($reservation, $paper);

        return $pdf->download('facture-reservation-' . ($reservation->reservation_number ?? $reservation->id) . '-' . $paper . '.pdf');
    }

    private function buildInvoicePdf(Reservation $reservation, string $paper): array
    {
        $reservation->load([
            'client',
            'room.apartment.hotel',
            'payments.user',
            'user',
            'manager',
        ]);

        $hotel = $reservation->room->apartment->hotel;
        $currency = $hotel->currency ?? 'FC';

        $paidAmount = (float) $reservation->payments->sum('amount');
        $grossAmount = $this->billingService->computeGrossTotal($reservation, $hotel);
        $discountAmount = (float) ($reservation->discount_amount ?? 0);
        $totalAmount = max(0, $grossAmount - $discountAmount);
        $remainingAmount = max(0, $totalAmount - $paidAmount);

        $expectedEndDate = $reservation->expected_checkout_date
            ?? $reservation->actual_checkout_date
            ?? now()->startOfDay();

        $expectedNights = max(1, $reservation->checkin_date->startOfDay()->diffInDays($expectedEndDate->startOfDay(), false));

        $actualNights = $reservation->computeNights(now(), $hotel->checkout_time);
        $pricePerNight = $actualNights > 0 ? round($grossAmount / $actualNights, 2) : $grossAmount;

        $paymentStatusLabel = [
            'paid' => 'PAYÉ',
            'partial' => 'PARTIEL',
            'unpaid' => 'NON PAYÉ',
        ][$reservation->payment_status] ?? strtoupper($reservation->payment_status);

        $logoDataUri = null;
        if (!empty($hotel->image)) {
            $logoPath = storage_path('app/public/' . $hotel->image);
            if (is_file($logoPath)) {
                $mime = $this->detectMimeType($logoPath);
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
            }
        }

        $publicA4Url = URL::temporarySignedRoute(
            'reservations.public.invoice.pdf',
            now()->addDays(7),
            ['reservation' => $reservation->id, 'paper' => 'a4']
        );

        $pdf = Pdf::loadView('pdf.invoice', compact(
            'reservation',
            'hotel',
            'paper',
            'paidAmount',
            'grossAmount',
            'discountAmount',
            'totalAmount',
            'remainingAmount',
            'expectedNights',
            'actualNights',
            'pricePerNight',
            'paymentStatusLabel',
            'currency',
            'logoDataUri',
            'publicA4Url'
        ));

        if ($paper === '80mm') {
            $pdf->setPaper([0, 0, 226.77, 900], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return [$reservation, $pdf];
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('checkin_date', [$request->date('from_date'), $request->date('to_date')]);
        } else {
            $query->where(function ($subQuery) {
                $subQuery->whereDate('checkin_date', today())
                    ->orWhere('status', 'reserved')
                    ->orWhereNotNull('deleted_at');
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

    private function detectMimeType(string $filePath): string
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
