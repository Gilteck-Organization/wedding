<?php

namespace App\Services\AccessCard;

class MalformedAccessCardUrlResolver
{
    private const TOKEN_PATTERN = '[a-z]{5}';

    /**
     * If the request targets a broken access-card URL, return the corrected path (with query string).
     */
    public static function resolve(string $requestUri): ?string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/access-card/')) {
            return null;
        }

        if (preg_match('#^/access-card/'.self::TOKEN_PATTERN.'(?:/|$)#', $path)) {
            return null;
        }

        $decodedPath = urldecode($path);

        if (! preg_match('#/access-card/('.self::TOKEN_PATTERN.')(?:/|$)#', $decodedPath, $matches)) {
            return null;
        }

        $token = $matches[1];
        $suffix = '';

        if (preg_match('#/access-card/'.preg_quote($token, '#').'(/.*)$#', $decodedPath, $suffixMatches)) {
            $suffix = $suffixMatches[1];
        }

        $query = parse_url($requestUri, PHP_URL_QUERY);
        $correctedPath = '/access-card/'.$token.$suffix;

        if (is_string($query) && $query !== '') {
            return $correctedPath.'?'.$query;
        }

        return $correctedPath;
    }
}
