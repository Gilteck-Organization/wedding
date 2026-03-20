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

        $primary = config('wedding.primary_rgb');
    @endphp

    <div class="min-h-svh flex items-center justify-center px-3 py-8 sm:px-6">
        <div class="access-card relative w-full max-w-[min(100vw-1.5rem,420px)]">
            <img src="{{ asset('images/access-card.png') }}" alt="" class="block h-auto w-full select-none"
                width="2480" height="3508" loading="eager" decoding="async">

            {{-- pt pushes QR + text slightly lower on the card --}}
            <div
                class="absolute inset-0 flex flex-col items-center justify-center gap-4 px-6 pt-[clamp(2.25rem,12%,5rem)] sm:px-8 sm:pt-[clamp(2.5rem,14%,5.5rem)]"
                aria-live="polite"
            >
                @if ($guest->is_approved && $guest->qr_code)
                    <div class="inline-block [&_svg]:block">
                        {!! QrCode::size(80)->margin(1)->color($primary['r'], $primary['g'], $primary['b'])->backgroundColor(255, 255, 255, 0)->generate($guest->qr_code) !!}
                    </div>

                    <div class="max-w-[90%] text-center">
                        <p
                            class="font-serif text-base leading-snug text-balance text-[#5a3e13] [text-shadow:0_1px_0_rgba(255,250,240,0.85),0_0_12px_rgba(250,246,238,0.9)]">
                            {{ $guest->name }}
                        </p>
                        @if ($additionalGuests !== null)
                            <p
                                class="mt-2 text-xs font-medium text-[#5a3e13]/90 [text-shadow:0_1px_0_rgba(255,250,240,0.85),0_0_10px_rgba(250,246,238,0.85)]">
                                and {{ $additionalGuests }} more
                            </p>
                        @endif
                    </div>
                @else
                    <div
                        class="max-w-[90%] rounded-sm border border-[#946112]/25 bg-[#fffdf8]/95 px-4 py-3 text-center text-sm text-[#2c2418] shadow-md">
                        QR code will be available once your RSVP is approved.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
