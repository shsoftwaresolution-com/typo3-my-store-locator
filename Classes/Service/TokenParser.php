<?php

declare(strict_types=1);

namespace Shss\MyStoreLocator\Service;

/**
 * Extracts a My Store Locator map token from what an editor pasted:
 * the raw token, the iframe or widget embed code, or a public map URL (…/#/m/<token>).
 */
final class TokenParser
{
    public const TOKEN_PATTERN = '/^[A-Za-z0-9_-]{6,64}$/';

    /**
     * Returns the token, or an empty string when the input contains no valid token.
     */
    public static function parse(?string $input): string
    {
        $input = trim((string)$input);
        if ($input === '') {
            return '';
        }
        $candidates = [];
        // widget code: data-token="…" or data-token='…'
        if (preg_match('/data-token\s*=\s*["\']([^"\']*)["\']/i', $input, $m)) {
            $candidates[] = $m[1];
        }
        // iframe code or public map URL: …/#/m/<token> (also URL-encoded "%23/m/")
        if (preg_match('~(?:#|%23)/m/([A-Za-z0-9_-]+)~', $input, $m)) {
            $candidates[] = $m[1];
        }
        $candidates[] = $input;
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if (self::isValid($candidate)) {
                return $candidate;
            }
        }
        return '';
    }

    public static function isValid(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }
}
