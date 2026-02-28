<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\ReservationBillingService;

class PaymentController extends Controller
{
    public function __construct(private readonly ReservationBillingService $billingService)
    {
    }

    public function store(StorePaymentRequest $request)
    {
        $reservation = Reservation::withTrashed()
            ->with(['room.apartment.hotel'])
            ->findOrFail($request->integer('reservation_id'));
        $this->authorize('update', $reservation);

        if ($reservation->trashed()) {
            return back()->withErrors(['reservation_id' => 'Cette réservation est annulée. Paiement impossible.']);
        }

        $hotel = $reservation->room->apartment->hotel;
        $computedTotal = $this->billingService->computeTotal($reservation, $hotel);
        if ((float) $reservation->total_amount !== (float) $computedTotal) {
            $reservation->update(['total_amount' => $computedTotal]);
            $reservation->refresh();
        }

        $requestedAmount = (float) $request->input('amount');

        Payment::create([
            'reservation_id' => $reservation->id,
            'id_user' => $request->user()->id,
            'amount' => $requestedAmount,
            'payment_method' => $request->string('payment_method')->toString(),
            'created_at' => now(),
        ]);

        $this->billingService->refreshPaymentStatus($reservation->fresh());

        return back()->with('success', 'Paiement enregistré avec succès.');
    }
}
