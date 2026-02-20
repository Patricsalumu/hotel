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
        $reservation = Reservation::withTrashed()->findOrFail($request->integer('reservation_id'));
        $this->authorize('update', $reservation);

        if ($reservation->trashed()) {
            return back()->withErrors(['reservation_id' => 'Cette réservation est annulée. Paiement impossible.']);
        }

        $alreadyPaid = (float) $reservation->payments()->sum('amount');
        $remaining = max(0, (float) $reservation->total_amount - $alreadyPaid);
        $requestedAmount = (float) $request->input('amount');
        $amount = $remaining > 0 ? min($requestedAmount, $remaining) : $requestedAmount;

        Payment::create([
            'reservation_id' => $reservation->id,
            'id_user' => $request->user()->id,
            'amount' => $amount,
            'payment_method' => $request->string('payment_method')->toString(),
            'created_at' => now(),
        ]);

        $this->billingService->refreshPaymentStatus($reservation->fresh());

        return back()->with('success', 'Paiement enregistré avec succès.');
    }
}
