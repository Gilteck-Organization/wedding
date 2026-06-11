<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cinzel-decorative:400,700" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #fffdf8; }
        .access-card-stage {
            position: relative;
            width: 1200px;
            margin: 0;
            --access-qr-top: 75%;
            --access-qr-left: 28%;
            --access-qr-transform: translate(-50%, -50%);
            --access-qr-size: 205px;
            --access-name-top: 58%;
            --access-name-left: 50%;
            --access-name-transform: translate(-50%, 0);
            --access-name-max-width: 88%;
        }
        .access-card-stage__art {
            display: block;
            width: 100%;
            height: auto;
        }
        .access-card-stage__qr {
            position: absolute;
            top: var(--access-qr-top);
            left: var(--access-qr-left);
            transform: var(--access-qr-transform);
            width: var(--access-qr-size);
            height: var(--access-qr-size);
        }
        .access-card-stage__qr-inner,
        .access-card-stage__qr-inner svg {
            display: block;
            width: 100%;
            height: 100%;
        }
        .access-card-stage__guest-details {
            position: absolute;
            top: var(--access-name-top);
            left: var(--access-name-left);
            transform: var(--access-name-transform);
            width: min(100%, var(--access-name-max-width));
            max-width: var(--access-name-max-width);
            text-align: center;
        }
        .guest-line {
            font-family: 'Cinzel Decorative', Georgia, serif;
            font-size: 40px;
            line-height: 1.25;
            color: #3a2c17;
            text-shadow: 0 1px 0 rgba(255, 250, 240, 0.85), 0 0 12px rgba(250, 246, 238, 0.9);
        }
        .guest-line strong { font-weight: 700; }
        .party-line {
            margin-top: 8px;
            font-family: 'Cinzel Decorative', Georgia, serif;
            font-size: 34px;
            font-weight: 600;
            line-height: 1.2;
            color: #3a2c17;
            text-shadow: 0 1px 0 rgba(255, 250, 240, 0.9), 0 0 8px rgba(250, 246, 238, 0.85);
        }
    </style>
</head>
<body>
    @php
        use SimpleSoftwareIO\QrCode\Facades\QrCode;

        $partySize = $guest->latestRsvp?->guest_count;
        $additionalGuests = $partySize !== null && $partySize > 1 ? $partySize - 1 : null;
        $qrRgb = config('wedding.access_card_qr_rgb');
        $qrSize = (int) config('wedding.access_card_image.qr_render_size', 400);
    @endphp
    <div class="access-card-stage">
        <img src="{{ asset('images/access card-temp.jpg') }}" alt="" class="access-card-stage__art" width="1200">

        @if ($guest->is_approved && $guest->qr_code)
            <div class="access-card-stage__qr">
                <div class="access-card-stage__qr-inner">
                    {!! QrCode::size($qrSize)->margin(1)->color($qrRgb['r'], $qrRgb['g'], $qrRgb['b'])->backgroundColor(255, 255, 255, 0)->generate($guest->qr_code) !!}
                </div>
            </div>

            <div class="access-card-stage__guest-details">
                <p class="guest-line">Guest: <strong>{{ $guest->name }}</strong></p>
                @if ($additionalGuests === 1)
                    <p class="party-line">Plus one guest</p>
                @elseif ($additionalGuests !== null && $additionalGuests > 1)
                    <p class="party-line">Plus {{ $additionalGuests }} guests</p>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
