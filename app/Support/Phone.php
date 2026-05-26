<?php

namespace App\Support;

class Phone
{
    /**
     * Normalize a phone number to WhatsApp's expected E.164 form: digits only,
     * including country code, no leading "+", no spaces, parens, or dashes.
     *
     * Examples:
     *  "+1 (984) 658-1828" -> "19846581828"
     *  "+234 813 000 0000" -> "2348130000000"
     *  "(353) 87 654 3210" -> "353876543210" (only if string already begins with country code)
     *
     * Returns null if the input cannot be coerced into a plausible E.164 number
     * (less than 8 digits is treated as invalid).
     */
    public static function toWhatsapp(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
