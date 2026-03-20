<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'qr_code',
        'is_approved',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function latestRsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class)->latestOfMany();
    }
}
