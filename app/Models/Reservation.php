<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation): void {
            if (empty($reservation->id_user) && auth()->check()) {
                $reservation->id_user = auth()->id();
            }
        });
    }

    protected $fillable = [
        'client_id',
        'room_id',
        'manager_id',
        'id_user',
        'checkin_date',
        'expected_checkout_date',
        'actual_checkout_date',
        'status',
        'payment_status',
        'total_amount',
        'discount_amount',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'expected_checkout_date' => 'date',
        'actual_checkout_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }

    public function computeNights(Carbon $now, string $checkoutTime): int
    {
        $start = Carbon::parse($this->checkin_date)->startOfDay();
        $usingCurrentDate = false;

        if ($this->actual_checkout_date) {
            $end = Carbon::parse($this->actual_checkout_date)->startOfDay();
        } elseif ($this->expected_checkout_date) {
            $end = Carbon::parse($this->expected_checkout_date)->startOfDay();
        } else {
            $usingCurrentDate = true;
            $end = $now->copy()->startOfDay();
        }

        $nights = max(1, $start->diffInDays($end));

        if ($usingCurrentDate && $now->format('H:i') > Carbon::parse($checkoutTime)->format('H:i')) {
            $nights++;
        }

        return $nights;
    }
}
