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
use Illuminate\Support\Facades\URL;

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
            'discount_amount' => (float) $request->input('discount_amount', 0),
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

        $creationSource = (string) $request->input('creation_source', 'reservations_index');
        $successMessage = $creationSource === 'dashboard_shortcut'
            ? 'Réservation enregistrée avec succès (depuis le raccourci du tableau de bord).'
            : 'Réservation enregistrée avec succès.';

        return redirect()
            ->route('reservations.index')
            ->with('success', $successMessage);
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

        $waText = "Bonjour {$reservation->client->name},\n";
        $waText .= "Reservation #{$reservation->id} - Chambre {$reservation->room->number}\n";
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
        $hotel->loadMissing('owner');

        $query = Reservation::query()
            ->with(['client', 'room', 'payments'])
            ->whereHas('room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->latest();

        $this->applyFilters($query, $request);
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

        return $pdf->download('facture-reservation-' . $reservation->id . '-' . $paper . '.pdf');
    }

    public function publicInvoicePdf(Request $request, Reservation $reservation)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $paper = $request->query('paper', 'a4') === '80mm' ? '80mm' : 'a4';
        [, $pdf] = $this->buildInvoicePdf($reservation, $paper);

        return $pdf->download('facture-reservation-' . $reservation->id . '-' . $paper . '.pdf');
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

        $expectedNights = $reservation->expected_checkout_date
            ? max(1, $reservation->checkin_date->diffInDays($reservation->expected_checkout_date))
            : 1;

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
