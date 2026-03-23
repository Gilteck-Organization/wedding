<?php

namespace App\Http\Controllers\Wedding;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\Rsvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    public function phoneAvailability(Request $request): JsonResponse
    {
        $phone = trim((string) $request->query('phone', ''));
        if ($phone === '') {
            return response()->json(['available' => true]);
        }

        $exists = Rsvp::query()->where('phone', $phone)->exists();

        return response()->json(['available' => ! $exists]);
    }

    public function store(StoreRsvpRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $capacity = (int) config('wedding.venue_capacity');
        $reserved = Rsvp::reservedSeatTotal();

        if ($reserved >= $capacity) {
            return redirect()
                ->route('wedding.home')
                ->withFragment('rsvp')
                ->withErrors([
                    'capacity' => 'RSVP is closed — our guest list is full. Thank you for thinking of us.',
                ]);
        }

        if ($validated['attendance'] === 'yes' && $reserved + 1 > $capacity) {
            return redirect()
                ->route('wedding.home')
                ->withFragment('rsvp')
                ->withInput()
                ->withErrors([
                    'capacity' => 'Not enough seats left. Our guest list is full.',
                ]);
        }

        $guestCount = $validated['attendance'] === 'yes' ? 1 : null;

        $rsvp = Rsvp::create([
            'guest_id' => null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'attendance' => $validated['attendance'],
            'guest_count' => $guestCount,
            'message' => $validated['message'] ?? null,
        ]);

        return redirect()
            ->route('wedding.home')
            ->withFragment('rsvp')
            ->with('rsvp_success', true);
    }
}
