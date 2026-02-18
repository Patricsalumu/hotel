<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (empty($payment->id_user) && auth()->check()) {
                $payment->id_user = auth()->id();
            }
        });
    }

    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'id_user',
        'amount',
        'payment_method',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
