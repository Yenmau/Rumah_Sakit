<?php
/**
 * CSRF Protection (COMP6695 Secure Programming)
 * Compares hashed submitted token against sessions.csrf_token in database
 */

namespace Backend\Core;

class CSRF
{
    public static function getToken(): string
    {
        return Auth::getRawCsrfToken();
    }

    public static function verify(Request $request): bool
    {
        // Safe HTTP methods don't need CSRF check
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        // Authenticate request to load session
        Auth::authenticate($request);
        $session = Auth::getCurrentSession();

        // Check X-CSRF-Token header first, then body parameter
        $submittedToken = $request->header('x-csrf-token')
            ?? $request->body('_csrf')
            ?? $request->body('csrf_token');

        if (!$submittedToken) {
            return false;
        }

        if ($session && !empty($session['csrf_token'])) {
            // Hash submitted token with SHA-256 and compare with database session csrf_token
            $hashedSubmitted = hash('sha256', (string) $submittedToken);
            return hash_equals($session['csrf_token'], $hashedSubmitted);
        }

        // In-memory fallback
        $rawCsrf = Auth::getRawCsrfToken();
        if ($rawCsrf) {
            return hash_equals($rawCsrf, (string) $submittedToken);
        }

        return false;
    }
}
