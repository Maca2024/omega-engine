<?php

declare(strict_types=1);

namespace App\Http\Security;

/**
 * CSRF Token Manager.
 *
 * FIXES TOXIC LEGACY CODE:
 * ```php
 * // NO CSRF PROTECTION AT ALL!
 * mysql_query("DELETE FROM orders WHERE id = ". $_GET['oid']);
 * ```
 *
 * NOW: Proper CSRF token validation.
 */
final class CsrfTokenManager
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = '_csrf_token';

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    public function getToken(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return $this->generateToken();
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function validateToken(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';

        if ($sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public function getTokenField(): string
    {
        return sprintf(
            '<input type="hidden" name="_csrf_token" value="%s">',
            htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8'),
        );
    }
}
