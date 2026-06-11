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
     * Public URL Meta can fetch for this guest's personalised access card image.
     */
    public function publicUrl(Guest $guest): string
    {
        $root = config('services.whatsapp.public_app_url');

        if (! is_string($root) || $root === '') {
            throw new RuntimeException(
                'WHATSAPP_PUBLIC_APP_URL is not set. Set your staging or production URL.'
            );
        }

        return rtrim($root, '/').'/access-card/'.$guest->access_token.'/image.jpg';
    }

    public function clearCache(Guest $guest): void
    {
        $path = $this->cachePath($guest);
        Storage::disk('local')->delete([$path, $path.'.meta']);
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
            'v5',
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

        $guestLine = 'Guest: '.$guest->name;
        $partyLine = $this->partyLine($guest);

        $maxWidth = (int) round($width * ((float) ($layout['name_max_width_percent'] ?? 88) / 100));
        $lineGap = (int) ($layout['line_gap_px'] ?? 3);

        $this->drawShadowedWrappedCenteredText(
            $base,
            $this->fontPath('bold'),
            $nameSize,
            $centerX,
            $topY,
            $maxWidth,
            $lineGap,
            $guestLine,
        );

        if ($partyLine !== null) {
            $partyGap = max($lineGap, (int) round($width * 0.008));

            $this->drawShadowedWrappedCenteredText(
                $base,
                $this->fontPath('regular'),
                $partySize,
                $centerX,
                $topY + $this->wrappedTextHeight($nameSize, $this->fontPath('bold'), $guestLine, $maxWidth, $lineGap) + $partyGap,
                $maxWidth,
                $lineGap,
                $partyLine,
            );
        }
    }

    private function wrappedTextHeight(
        int $fontSize,
        string $fontPath,
        string $text,
        int $maxWidth,
        int $lineGap,
    ): int {
        $lines = $this->wrapText($text, $fontPath, $fontSize, $maxWidth);

        if ($lines === []) {
            return 0;
        }

        $box = imagettfbbox($fontSize, 0, $fontPath, $lines[0]);

        if ($box === false) {
            return $fontSize;
        }

        $lineHeight = abs($box[7] - $box[1]);

        return ($lineHeight * count($lines)) + ($lineGap * max(0, count($lines) - 1));
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

    private function drawShadowedWrappedCenteredText(
        GdImage $image,
        string $fontPath,
        int $fontSize,
        int $centerX,
        int $topY,
        int $maxWidth,
        int $lineGap,
        string $text,
    ): int {
        if (! function_exists('imagettftext')) {
            throw new RuntimeException('PHP FreeType support is required to render guest names on access cards.');
        }

        $lines = $this->wrapText($text, $fontPath, $fontSize, $maxWidth);
        $shadow = imagecolorallocatealpha($image, 250, 246, 238, 20);
        $textColor = imagecolorallocate($image, 58, 44, 23);
        $baselineY = $topY;

        foreach ($lines as $line) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $line);

            if ($box === false) {
                throw new RuntimeException('Could not measure access card text: '.$line);
            }

            $textWidth = abs($box[2] - $box[0]);
            $lineHeight = abs($box[7] - $box[1]);
            $x = $centerX - (int) round($textWidth / 2);
            $baselineY += $lineHeight;

            foreach ([[-1, 0], [1, 0], [0, -1], [0, 1], [-1, -1], [1, 1]] as [$ox, $oy]) {
                imagettftext($image, $fontSize, 0, $x + $ox, $baselineY + $oy, $shadow, $fontPath, $line);
            }

            $result = imagettftext($image, $fontSize, 0, $x, $baselineY, $textColor, $fontPath, $line);

            if ($result === false) {
                throw new RuntimeException('Failed to draw access card text: '.$line);
            }

            $baselineY += $lineGap;
        }

        return count($lines);
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($fontSize, 0, $fontPath, $candidate);

            if ($box === false) {
                throw new RuntimeException('Could not measure access card text: '.$candidate);
            }

            $candidateWidth = abs($box[2] - $box[0]);

            if ($candidateWidth > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines !== [] ? $lines : [$text];
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
