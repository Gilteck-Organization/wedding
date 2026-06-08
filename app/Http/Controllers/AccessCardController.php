<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnlockAccessCardRequest;
use App\Models\AccessName;
use App\Models\Guest;
use App\Services\AccessCard\AccessCardImageGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccessCardController extends Controller
{
    /**
     * Public access card (visual + QR). No login or name check.
     */
    public function show(Guest $guest): View
    {
        $guest->load('latestRsvp');

        return view('wedding.access-card', [
            'guest' => $guest,
        ]);
    }

    /**
     * Personalised access card JPEG for WhatsApp template headers and other integrations.
     * Meta's servers fetch this URL when sending the approved template.
     */
    public function image(Guest $guest, AccessCardImageGenerator $generator): BinaryFileResponse
    {
        $guest->load('latestRsvp');

        try {
            $path = $generator->ensureCached($guest);
        } catch (RuntimeException) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * URL encoded in the QR on the card: global access name for guests, verified page for staff or after success.
     */
    public function verify(Guest $guest): View|RedirectResponse
    {
        $guest->load('latestRsvp');

        if (! $guest->is_approved || ! $guest->qr_code) {
            return redirect()->route('wedding.home');
        }

        if (auth()->check()) {
            return view('wedding.access-card-admin-verify', [
                'guest' => $guest,
            ]);
        }

        if (session()->get($guest->sessionUnlockKey())) {
            return view('wedding.access-card-admin-verify', [
                'guest' => $guest,
            ]);
        }

        return view('wedding.access-card-name-gate', [
            'guest' => $guest,
        ]);
    }

    public function verifySubmit(UnlockAccessCardRequest $request, Guest $guest): RedirectResponse
    {
        if (! $guest->is_approved || ! $guest->qr_code) {
            return redirect()->route('wedding.home');
        }

        if (! AccessName::matches($request->validated('name'))) {
            return redirect()->route('wedding.home');
        }

        session()->put($guest->sessionUnlockKey(), true);

        return redirect()->route('access-card.verify', $guest);
    }
}
