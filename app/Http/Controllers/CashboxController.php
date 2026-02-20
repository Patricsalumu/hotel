<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseAccount;
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
        $expenseAccountId = $request->integer('expense_account_id') ?: null;

        $payments = Payment::with('reservation.room')
            ->whereHas('reservation.room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->get();

        $expensesQuery = Expense::with('account')
            ->where('hotel_id', $hotel->id)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at');

        if ($expenseAccountId) {
            $expensesQuery->where('account_id', $expenseAccountId);
        }

        $expenses = $expensesQuery->get();
        $expenseAccounts = ExpenseAccount::where('hotel_id', $hotel->id)->orderBy('name')->get();

        $totalIn = (float) $payments->sum('amount');
        $totalOut = (float) $expenses->sum('amount');

        return view('cashbox.index', compact(
            'payments',
            'expenses',
            'totalIn',
            'totalOut',
            'from',
            'to',
            'hotel',
            'expenseAccounts',
            'expenseAccountId'
        ));
    }

    public function exportPdf(Request $request)
    {
        $hotel = $request->user()->currentHotel();
        $hotel->loadMissing('owner');

        $from = $request->date('from_date') ?? today();
        $to = $request->date('to_date') ?? today();
        $expenseAccountId = $request->integer('expense_account_id') ?: null;

        $payments = Payment::with('reservation.room')
            ->whereHas('reservation.room.apartment', fn ($q) => $q->where('hotel_id', $hotel->id))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at')
            ->get();

        $expensesQuery = Expense::with('account')
            ->where('hotel_id', $hotel->id)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('created_at');

        if ($expenseAccountId) {
            $expensesQuery->where('account_id', $expenseAccountId);
        }

        $expenses = $expensesQuery->get();

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

        $pdf = Pdf::loadView('pdf.cashbox', compact(
            'payments',
            'expenses',
            'hotel',
            'from',
            'to',
            'currency',
            'enterpriseEmail',
            'enterpriseAddress',
            'logoDataUri'
        ));
        return $pdf->download('cashbox-report.pdf');
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
