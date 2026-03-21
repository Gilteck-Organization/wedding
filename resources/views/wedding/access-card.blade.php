@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    {{-- Wedding monogram preloader (F & K) --}}
    <div id="wedding-preloader" class="wedding-preloader" aria-busy="true" aria-live="polite" role="status">
        <div class="wedding-preloader__frame">
            <p class="wedding-preloader__eyebrow">Together with their families</p>
            <div class="wedding-preloader__monogram" aria-hidden="true">
                <span class="wedding-preloader__letter">F</span>
                <span class="wedding-preloader__divider"></span>
                <span class="wedding-preloader__letter">K</span>
            </div>
            <p class="wedding-preloader__names">Fifi &amp; Kiki</p>
        </div>
    </div>

    @php
        use SimpleSoftwareIO\QrCode\Facades\QrCode;

        $partySize = $guest->latestRsvp?->guest_count;
        $additionalGuests = $partySize !== null && $partySize > 1 ? $partySize - 1 : null;

        $qrRgb = config('wedding.access_card_qr_rgb');
    @endphp

    {{-- items-start + modest top padding: less empty space above the card on tall screens --}}
    <div class="access-card-page flex min-h-svh items-start justify-center px-3 pt-6 pb-12 sm:px-6 sm:pt-8 sm:pb-16">
        {{--
            Card art: public/images/access card-temp.jpg
            Position QR + text in resources/css/app.css → .access-card-stage { --access-* }
        --}}
        <div class="access-card-stage">
            <img src="{{ asset('images/access card-temp.jpg') }}" alt="" class="access-card-stage__art"
                width="4419" height="6250" loading="eager" decoding="async">

            @if ($guest->is_approved && $guest->qr_code)
                <div class="access-card-stage__qr" aria-hidden="false">
                    <div class="access-card-stage__qr-inner">
                        {{-- size() = SVG pixel density; 4.2cm display ≈ ~160px CSS — use extra pixels for sharp scaling / print --}}
                        {!! QrCode::size(80)->margin(1)->color($qrRgb['r'], $qrRgb['g'], $qrRgb['b'])->backgroundColor(255, 255, 255, 0)->generate($guest->qr_code) !!}
                    </div>
                </div>

                <div class="access-card-stage__guest-details mt-4" aria-live="polite">
                    <p
                        class="font-serif text-sm leading-snug text-balance text-wedding-champagne [text-shadow:0_1px_0_rgba(255,250,240,0.85),0_0_12px_rgba(250,246,238,0.9)]">
                        Guest: <span class="font-bold">{{ $guest->name }}</span>
                    </p>
                    @if ($additionalGuests === 1)
                        <p
                            class="mt-0.5 font-serif text-xs font-semibold leading-tight text-[#3a2c17] [text-shadow:0_1px_0_rgba(255,250,240,0.9),0_0_8px_rgba(250,246,238,0.85)]">
                            Plus one guest
                        </p>
                    @elseif ($additionalGuests !== null && $additionalGuests > 1)
                        <p
                            class="mt-0.5 font-serif text-xs font-medium text-[#5a3e13]/90 [text-shadow:0_1px_0_rgba(255,250,240,0.85),0_0_10px_rgba(250,246,238,0.85)]">
                            Plus {{ $additionalGuests }} guests
                        </p>
                    @endif
                </div>
            @else
                <div class="access-card-stage__notice" role="status">
                    <div
                        class="rounded-sm border border-[#946112]/25 bg-[#fffdf8]/95 px-4 py-3 text-center text-sm text-[#2c2418] shadow-md">
                        QR code will be available once your RSVP is approved.
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
