<?php

declare(strict_types=1);

use App\Http\Security\CsrfTokenManager;

describe('CsrfTokenManager', function (): void {

    beforeEach(function (): void {
        // Start session for tests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Clear any existing token
        unset($_SESSION['_csrf_token']);

        $this->manager = new CsrfTokenManager();
    });

    afterEach(function (): void {
        // Clean up session
        $_SESSION = [];
    });

    it('generates token on first call', function (): void {
        $token = $this->manager->generateToken();

        expect($token)->toBeString();
        expect(strlen($token))->toBe(64); // 32 bytes = 64 hex chars
    });

    it('stores token in session', function (): void {
        $token = $this->manager->generateToken();

        expect($_SESSION['_csrf_token'])->toBe($token);
    });

    it('returns same token on getToken when already generated', function (): void {
        $token1 = $this->manager->generateToken();
        $token2 = $this->manager->getToken();

        expect($token2)->toBe($token1);
    });

    it('generates new token on getToken when none exists', function (): void {
        $token = $this->manager->getToken();

        expect($token)->toBeString();
        expect(strlen($token))->toBe(64);
    });

    it('validates correct token', function (): void {
        $token = $this->manager->generateToken();

        expect($this->manager->validateToken($token))->toBeTrue();
    });

    it('rejects null token', function (): void {
        $this->manager->generateToken();

        expect($this->manager->validateToken(null))->toBeFalse();
    });

    it('rejects empty token', function (): void {
        $this->manager->generateToken();

        expect($this->manager->validateToken(''))->toBeFalse();
    });

    it('rejects invalid token', function (): void {
        $this->manager->generateToken();

        expect($this->manager->validateToken('invalid-token'))->toBeFalse();
    });

    it('rejects when no token in session', function (): void {
        expect($this->manager->validateToken('some-token'))->toBeFalse();
    });

    it('generates HTML token field', function (): void {
        $field = $this->manager->getTokenField();

        expect($field)->toContain('<input type="hidden"');
        expect($field)->toContain('name="_csrf_token"');
        expect($field)->toContain('value="');
    });

});
