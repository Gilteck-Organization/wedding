<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessName extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Whether the entered string matches any global access name (same rules as before: trim, spaces, case).
     */
    public static function matches(string $entered): bool
    {
        $normalized = Guest::normalizeNameForMatch($entered);
        if ($normalized === '') {
            return false;
        }

        if (! static::query()->exists()) {
            return false;
        }

        return static::query()
            ->get()
            ->contains(fn (AccessName $row): bool => Guest::normalizeNameForMatch($row->name) === $normalized);
    }
}
