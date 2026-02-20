<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\ExpenseAccount;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request)
    {
        $this->authorize('create', Expense::class);
        $hotel = $request->user()->currentHotel();
        $account = ExpenseAccount::where('id', $request->integer('account_id'))
            ->where('hotel_id', $hotel->id)
            ->firstOrFail();

        Expense::create([
            'hotel_id' => $hotel->id,
            'user_id' => $request->user()->id,
            'account_id' => $account->id,
            'category' => 'autres',
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Dépense ajoutée avec succès.');
    }
}
