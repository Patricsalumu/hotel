<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'hotel_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function ownedHotel(): HasOne
    {
        return $this->hasOne(Hotel::class, 'owner_id');
    }

    public function managedReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'manager_id');
    }

    public function createdReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'id_user');
    }

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'id_user');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function currentHotel(): ?Hotel
    {
        if ($this->role === 'owner') {
            return $this->ownedHotel;
        }

        return $this->hotel;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
