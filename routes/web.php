<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseAccountController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomLayoutController;
use App\Http\Controllers\SuperAdminHotelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth'])->name('dashboard');
Route::get('/public/reservations/{reservation}/invoice', [ReservationController::class, 'publicInvoicePdf'])
    ->middleware('signed')
    ->name('reservations.public.invoice.pdf');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('reservations', ReservationController::class)->only(['index', 'store', 'show', 'update']);
    Route::get('/reports/reservations/pdf', [ReservationController::class, 'exportPdf'])->name('reports.reservations.pdf');
    Route::get('/reservations/{reservation}/invoice', [ReservationController::class, 'invoicePdf'])->name('reservations.invoice.pdf');

    Route::resource('clients', ClientController::class)->only(['index', 'store', 'show']);
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::post('/clients/quick-store', [ClientController::class, 'quickStore'])->name('clients.quick-store');

    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

    Route::get('/cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
    Route::get('/cashbox/accounts', [ExpenseAccountController::class, 'index'])->name('cashbox.accounts');
    Route::get('/cashbox/pdf', [CashboxController::class, 'exportPdf'])->name('cashbox.pdf');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('/expense-accounts', [ExpenseAccountController::class, 'store'])->name('expense-accounts.store');
    Route::patch('/expense-accounts/{expense_account}', [ExpenseAccountController::class, 'update'])->name('expense-accounts.update');

    Route::middleware('role:owner')->group(function () {
        Route::resource('owner/hotels', HotelController::class)->only(['index', 'store'])->names('owner.hotels');
        Route::resource('owner/apartments', ApartmentController::class)->only(['index', 'store'])->names('owner.apartments');
        Route::resource('owner/rooms', RoomController::class)->only(['index', 'store', 'update'])->names('owner.rooms');
        Route::post('/owner/rooms/layout', [RoomLayoutController::class, 'update'])->name('owner.rooms.layout.update');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/superadmin/hotels', [SuperAdminHotelController::class, 'index'])->name('superadmin.hotels.index');
        Route::post('/superadmin/hotels', [SuperAdminHotelController::class, 'store'])->name('superadmin.hotels.store');
        Route::post('/superadmin/users', [SuperAdminHotelController::class, 'storeUser'])->name('superadmin.users.store');
        Route::post('/superadmin/users/link', [SuperAdminHotelController::class, 'linkUser'])->name('superadmin.users.link');
    });
});

require __DIR__.'/auth.php';
