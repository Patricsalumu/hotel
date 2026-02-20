<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'city',
        'phone',
        'currency',
        'image',
        'checkout_time',
        'note',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseAccounts(): HasMany
    {
        return $this->hasMany(ExpenseAccount::class);
    }
}
