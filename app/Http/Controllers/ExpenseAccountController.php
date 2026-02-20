<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseAccountRequest;
use App\Http\Requests\UpdateExpenseAccountRequest;
use App\Models\ExpenseAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseAccountController extends Controller
{
    public function index(Request $request)
    {
        $hotel = $request->user()->currentHotel();
        $from = $request->date('from_date') ?? today();
        $to = $request->date('to_date') ?? today();

        $expenseAccounts = ExpenseAccount::where('hotel_id', $hotel->id)
            ->orderBy('name')
            ->get();

        $accountStats = DB::table('expense_accounts as a')
            ->leftJoin('expenses as e', function ($join) use ($from, $to) {
                $join->on('e.account_id', '=', 'a.id')
                    ->whereBetween('e.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
            })
            ->where('a.hotel_id', $hotel->id)
            ->groupBy('a.id', 'a.name', 'a.description')
            ->selectRaw('a.id, a.name, a.description, COALESCE(SUM(e.amount), 0) as total_amount, COUNT(e.id) as expenses_count')
            ->orderByDesc('total_amount')
            ->get();

        $topAccountId = optional($accountStats->first())->id;

        return view('cashbox.accounts', compact(
            'hotel',
            'from',
            'to',
            'expenseAccounts',
            'accountStats',
            'topAccountId'
        ));
    }

    public function store(StoreExpenseAccountRequest $request)
    {
        $hotel = $request->user()->currentHotel();

        ExpenseAccount::create([
            'hotel_id' => $hotel->id,
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
        ]);

        return back()->with('success', 'Compte de dépense créé avec succès.');
    }

    public function update(UpdateExpenseAccountRequest $request, ExpenseAccount $expenseAccount)
    {
        $hotel = $request->user()->currentHotel();

        if ((int) $expenseAccount->hotel_id !== (int) $hotel->id) {
            abort(403);
        }

        $expenseAccount->update([
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
        ]);

        return back()->with('success', 'Compte de dépense renommé avec succès.');
    }
}
