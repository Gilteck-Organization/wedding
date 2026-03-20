<?php

namespace App\Http\Controllers\Wedding;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\Rsvp;

class RsvpController extends Controller
{
    public function store(StoreRsvpRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $rsvp = Rsvp::create([
            'guest_id' => null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'attendance' => $validated['attendance'],
            'guest_count' => $validated['guest_count'] ?? null,
            'message' => $validated['message'] ?? null,
        ]);

        return redirect()
            ->route('wedding.home')
            ->withFragment('rsvp')
            ->with('rsvp_success', true);
    }
}
