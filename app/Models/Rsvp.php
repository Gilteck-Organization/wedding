<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'guest_id',
        'name',
        'phone',
        'attendance',
        'guest_count',
        'message',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'guest_count' => 'integer',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
