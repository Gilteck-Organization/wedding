@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $partySize = $guest->latestRsvp?->guest_count;
    $additionalGuests = $partySize !== null && $partySize > 1 ? $partySize - 1 : null;
    $qrRgb = config('wedding.access_card_qr_rgb');
@endphp

<div class="access-card-stage" @if (empty($captureOnly)) data-share-access-card-target @endif>
    <img src="/images/access%20card-temp.jpg" alt="" class="access-card-stage__art"
        width="4419" height="6250" loading="eager" decoding="async">

    @if ($guest->is_approved && $guest->qr_code)
        <div class="access-card-stage__qr" aria-hidden="false">
            <div class="access-card-stage__qr-inner">
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
