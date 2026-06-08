<?php

namespace App\Services\AccessCard;

use App\Models\Guest;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AccessCardImageGenerator
{
    /**
     * Public URL Meta fetches for this guest's personalised access card image.
     * Built from WHATSAPP_PUBLIC_APP_URL + /access-card/{token}/image.jpg
     */
    public function publicUrl(Guest $guest): string
    {
        $root = config('services.whatsapp.public_app_url');

        if (! is_string($root) || $root === '') {
            throw new RuntimeException(
                'WHATSAPP_PUBLIC_APP_URL is not set. WhatsApp sends are disabled on this environment.'
            );
        }

        return rtrim($root, '/').'/access-card/'.$guest->access_token.'/image.jpg';
    }

    /**
     * Ensure a cached JPEG exists for the guest and return its absolute path.
     */
    public function ensureCached(Guest $guest): string
    {
        $guest->loadMissing('latestRsvp');

        $path = $this->cachePath($guest);
        $fingerprint = $this->cacheFingerprint($guest);
        $metaPath = $path.'.meta';

        if (
            Storage::disk('local')->exists($path)
            && Storage::disk('local')->exists($metaPath)
            && Storage::disk('local')->get($metaPath) === $fingerprint
        ) {
            return Storage::disk('local')->path($path);
        }

        $this->writeCache($guest, $fingerprint);

        return Storage::disk('local')->path($path);
    }

    /**
     * Render the access card to a JPEG binary string (template + guest name + QR).
     */
    public function render(Guest $guest): string
    {
        $guest->loadMissing('latestRsvp');
        $this->assertRenderable($guest);

        $base = $this->loadTemplateImage();

        $width = imagesx($base);
        $height = imagesy($base);

        $this->drawGuestDetails($base, $guest, $width, $height);
        $this->drawQrCode($base, $guest, $width, $height);

        ob_start();
        $quality = (int) config('wedding.access_card_image.jpeg_quality', 88);
        imagejpeg($base, null, $quality);
        imagedestroy($base);
        $binary = ob_get_clean();

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('Failed to encode access card image.');
        }

        return $binary;
    }

    private function writeCache(Guest $guest, string $fingerprint): void
    {
        $path = $this->cachePath($guest);
        Storage::disk('local')->put($path, $this->render($guest));
        Storage::disk('local')->put($path.'.meta', $fingerprint);
    }

    private function cacheFingerprint(Guest $guest): string
    {
        return hash('sha256', implode('|', [
            $guest->name,
            (string) $guest->qr_code,
            (string) $guest->latestRsvp?->guest_count,
            json_encode(config('wedding.access_card_image')),
        ]));
    }

    private function cachePath(Guest $guest): string
    {
        return 'access-cards/'.$guest->access_token.'.jpg';
    }

    private function assertRenderable(Guest $guest): void
    {
        if (! $guest->is_approved || ! filled($guest->qr_code)) {
            throw new RuntimeException('Access card image requires an approved guest with a QR code.');
        }
    }

    private function drawQrCode(GdImage $base, Guest $guest, int $width, int $height): void
    {
        $layout = config('wedding.access_card_image');
        $qrSize = (int) round($width * ((float) ($layout['qr_size_percent'] ?? 17.14) / 100));
        $qrCenterX = (int) round($width * ((float) ($layout['qr_left_percent'] ?? 28) / 100));
        $qrCenterY = (int) round($height * ((float) ($layout['qr_top_percent'] ?? 75) / 100));
        $destX = $qrCenterX - (int) round($qrSize / 2);
        $destY = $qrCenterY - (int) round($qrSize / 2);

        $qrImage = $this->loadQrImage($guest, $qrSize);

        imagecopyresampled(
            $base,
            $qrImage,
            $destX,
            $destY,
            0,
            0,
            $qrSize,
            $qrSize,
            imagesx($qrImage),
            imagesy($qrImage),
        );

        imagedestroy($qrImage);
    }

    private function drawGuestDetails(GdImage $base, Guest $guest, int $width, int $height): void
    {
        $layout = config('wedding.access_card_image');
        $centerX = (int) round($width * ((float) ($layout['name_left_percent'] ?? 50) / 100));
        $topY = (int) round($height * ((float) ($layout['name_top_percent'] ?? 58) / 100));

        $nameSize = (int) round($width * ((float) ($layout['name_font_size_percent'] ?? 3.4) / 100));
        $partySize = (int) round($width * ((float) ($layout['party_font_size_percent'] ?? 2.9) / 100));

        $textColor = imagecolorallocate($base, 58, 44, 23);

        $this->drawCenteredText(
            $base,
            $this->fontPath('bold'),
            $nameSize,
            $textColor,
            $centerX,
            $topY + $nameSize,
            'Guest: '.$guest->name,
        );

        $partyLine = $this->partyLine($guest);

        if ($partyLine === null) {
            return;
        }

        $this->drawCenteredText(
            $base,
            $this->fontPath('regular'),
            $partySize,
            $textColor,
            $centerX,
            $topY + $nameSize + $partySize + (int) round($width * 0.012),
            $partyLine,
        );
    }

    private function partyLine(Guest $guest): ?string
    {
        $partySize = $guest->latestRsvp?->guest_count;
        $additionalGuests = $partySize !== null && $partySize > 1 ? $partySize - 1 : null;

        if ($additionalGuests === 1) {
            return 'Plus one guest';
        }

        if ($additionalGuests !== null && $additionalGuests > 1) {
            return 'Plus '.$additionalGuests.' guests';
        }

        return null;
    }

    private function drawCenteredText(
        GdImage $image,
        string $fontPath,
        int $fontSize,
        int $color,
        int $centerX,
        int $baselineY,
        string $text,
    ): void {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = abs($box[2] - $box[0]);
        $x = $centerX - (int) round($textWidth / 2);

        imagettftext($image, $fontSize, 0, $x, $baselineY, $color, $fontPath, $text);
    }

    private function fontPath(string $weight): string
    {
        $file = $weight === 'bold'
            ? 'CinzelDecorative-Bold.ttf'
            : 'CinzelDecorative-Regular.ttf';

        $path = resource_path('fonts/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException('Access card font is missing: '.$file);
        }

        return $path;
    }

    private function loadTemplateImage(): GdImage
    {
        $path = $this->scaledTemplatePath();

        if (! is_file($path)) {
            $this->buildScaledTemplate($path);
        }

        $image = @imagecreatefromjpeg($path);

        if ($image === false) {
            throw new RuntimeException('Access card template image could not be loaded.');
        }

        return $image;
    }

    private function scaledTemplatePath(): string
    {
        return storage_path('app/access-cards/_template-base.jpg');
    }

    private function buildScaledTemplate(string $destination): void
    {
        $sourcePath = public_path('images/access card-temp.jpg');

        if (! is_file($sourcePath)) {
            throw new RuntimeException('Access card template image is missing.');
        }

        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $directory = dirname($destination);

            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException('Could not create access card cache directory.');
            }

            $source = imagecreatefromjpeg($sourcePath);

            if ($source === false) {
                throw new RuntimeException('Access card template image could not be loaded.');
            }

            $scaled = $this->scaleToMaxWidth($source, (int) config('wedding.access_card_image.max_width', 1200));
            $written = imagejpeg($scaled, $destination, 90);
            imagedestroy($scaled);

            if ($written !== true) {
                throw new RuntimeException('Failed to write scaled access card template cache.');
            }
        } finally {
            if (is_string($previousLimit) && $previousLimit !== '') {
                @ini_set('memory_limit', $previousLimit);
            }
        }
    }

    private function scaleToMaxWidth(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $newHeight = (int) round($height * ($maxWidth / $width));
        $scaled = imagescale($image, $maxWidth, $newHeight, IMG_BILINEAR_FIXED);
        imagedestroy($image);

        if ($scaled === false) {
            throw new RuntimeException('Failed to scale access card template image.');
        }

        return $scaled;
    }

    private function loadQrImage(Guest $guest, int $size): GdImage
    {
        $rgb = config('wedding.access_card_qr_rgb');

        $binary = QrCode::format('png')
            ->size($size)
            ->margin(1)
            ->color($rgb['r'], $rgb['g'], $rgb['b'])
            ->backgroundColor(255, 255, 255, 0)
            ->generate((string) $guest->qr_code);

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            throw new RuntimeException('Failed to generate QR code image.');
        }

        return $image;
    }
}
