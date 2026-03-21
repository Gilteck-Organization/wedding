<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Guest extends Model
{
    private const ACCESS_TOKEN_LENGTH = 5;

    private const ACCESS_TOKEN_ALPHABET = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'access_token',
        'qr_code',
        'is_approved',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Guest $guest): void {
            if ($guest->access_token === null || $guest->access_token === '') {
                $guest->access_token = static::newAccessToken();
            }
        });
    }

    public static function newAccessToken(): string
    {
        $alphabet = static::ACCESS_TOKEN_ALPHABET;
        $max = strlen($alphabet) - 1;
        $length = static::ACCESS_TOKEN_LENGTH;

        do {
            $token = '';
            for ($i = 0; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, $max)];
            }
        } while (static::query()->where('access_token', $token)->exists());

        return $token;
    }

    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function latestRsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class)->latestOfMany();
    }

    public static function normalizeNameForMatch(string $name): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', $name);
        $name = trim(is_string($collapsed) ? $collapsed : '');

        return Str::lower($name);
    }

    public function sessionUnlockKey(): string
    {
        return 'access_card_unlocked.'.$this->access_token;
    }
}
