<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'apartment_id',
        'room_id',
        'hotel_id',
        'reservation_number',
        'manager_id',
        'id_user',
        'expected_checkin_date',
        'checkin_date',
        'expected_checkout_date',
        'actual_checkout_date',
        'status',
        'payment_status',
        'total_amount',
        'discount_amount',
    ];

    protected $casts = [
        'expected_checkin_date' => 'date',
        'checkin_date' => 'datetime',
        'expected_checkout_date' => 'date',
        'actual_checkout_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'reservation_number' => 'string',
        'deleted_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
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
        $start = Carbon::parse($this->checkin_date ?? $this->expected_checkin_date ?? $now->toDateString())->startOfDay();
        $today = $now->copy()->startOfDay();

        if ($this->actual_checkout_date) {
            $end = Carbon::parse($this->actual_checkout_date)->startOfDay();
            $useCurrentDate = false;
        } elseif ($this->expected_checkout_date && $today->gte(Carbon::parse($this->expected_checkout_date)->startOfDay())) {
            $end = Carbon::parse($this->expected_checkout_date)->startOfDay();
            $useCurrentDate = false;
        } else {
            $end = $today;
            $useCurrentDate = true;
        }

        $nights = max(1, $start->diffInDays($end, false));

        if (
            $useCurrentDate &&
            $today->gt($start) &&
            $now->format('H:i') > Carbon::parse($checkoutTime)->format('H:i')
        ) {
            $nights++;
        }

        return $nights;
    }

    public function getReferenceAttribute(): string
    {
        return 'RES-' . ($this->reservation_number ?? $this->id);
    }
}
