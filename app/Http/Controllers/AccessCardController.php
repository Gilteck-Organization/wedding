<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnlockAccessCardRequest;
use App\Models\AccessName;
use App\Models\Guest;
use App\Services\AccessCard\AccessCardImageGenerator;
use App\Services\AccessCard\MalformedAccessCardUrlResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * URL encoded in the QR on the card: one-time check-in via access name (or admin scan).
     */
    public function verify(Guest $guest): View|RedirectResponse
    {
        $guest->load('latestRsvp');

        if (! $guest->is_approved || ! $guest->qr_code) {
            return redirect()->route('wedding.home');
        }

        if ($guest->isQrVerified()) {
            return view('wedding.access-card-already-scanned', [
                'guest' => $guest,
            ]);
        }

        if (auth()->check()) {
            $this->markQrVerifiedOnce($guest);

            return view('wedding.access-card-admin-verify', [
                'guest' => $guest->fresh('latestRsvp'),
            ]);
        }

        return view('wedding.access-card-name-gate', [
            'guest' => $guest,
        ]);
    }

    public function verifySubmit(UnlockAccessCardRequest $request, Guest $guest): RedirectResponse|View
    {
        if (! $guest->is_approved || ! $guest->qr_code) {
            return redirect()->route('wedding.home');
        }

        if ($guest->isQrVerified()) {
            return redirect()->route('access-card.verify', $guest);
        }

        if (! AccessName::matches($request->validated('name'))) {
            return redirect()->route('wedding.home');
        }

        if (! $this->markQrVerifiedOnce($guest)) {
            return redirect()->route('access-card.verify', $guest);
        }

        $guest->load('latestRsvp');

        return view('wedding.access-card-admin-verify', [
            'guest' => $guest,
        ]);
    }

    /**
     * Catch malformed WhatsApp button URLs that do not match the 5-letter token routes.
     */
    public function redirectMalformed(Request $request): RedirectResponse
    {
        $corrected = MalformedAccessCardUrlResolver::resolve($request->getRequestUri());

        if ($corrected === null) {
            abort(404);
        }

        return redirect()->to($corrected, 302);
    }

    private function markQrVerifiedOnce(Guest $guest): bool
    {
        return DB::transaction(function () use ($guest): bool {
            $lockedGuest = Guest::query()->lockForUpdate()->find($guest->getKey());

            if ($lockedGuest === null) {
                return false;
            }

            return $lockedGuest->markQrVerified();
        });
    }
}
