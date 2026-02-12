<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CashboxController extends Controller
{
    public function index(Request $request)
    {
        $hotel = $request->user()->currentHotel();
        $from = $request->date('from_date') ?? today();
        $to = $request->date('to_date') ?? today();

        $payments = Payment::with('reservation.room')
            ->whereHas('reservation.room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->get();

        $expenses = Expense::where('hotel_id', $hotel->id)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->get();

        $totalIn = (float) $payments->sum('amount');
        $totalOut = (float) $expenses->sum('amount');

        return view('cashbox.index', compact('payments', 'expenses', 'totalIn', 'totalOut', 'from', 'to'));
    }

    public function exportPdf(Request $request)
    {
        $hotel = $request->user()->currentHotel();
        $from = $request->date('from_date') ?? today();
        $to = $request->date('to_date') ?? today();

        $payments = Payment::with('reservation.room')
            ->whereHas('reservation.room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->get();

        $expenses = Expense::where('hotel_id', $hotel->id)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.cashbox', compact('payments', 'expenses', 'hotel', 'from', 'to'));
        return $pdf->download('cashbox-report.pdf');
    }
}
