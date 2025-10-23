<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\CSRF;
use App\Session;

class CSRFTest extends TestCase
{
    protected function setUp(): void
    {
        // Start a new session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testGenerateTokenCreatesToken(): void
    {
        $token = CSRF::generateToken();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testGetTokenReturnsSameToken(): void
    {
        $token1 = CSRF::getToken();
        $token2 = CSRF::getToken();
        
        $this->assertEquals($token1, $token2);
    }

    public function testValidateTokenWithValidToken(): void
    {
        $token = CSRF::generateToken();
        
        $this->assertTrue(CSRF::validateToken($token));
    }

    public function testValidateTokenWithInvalidToken(): void
    {
        CSRF::generateToken();
        
        $this->assertFalse(CSRF::validateToken('invalid-token'));
    }

    public function testFieldGeneratesHiddenInput(): void
    {
        $field = CSRF::field();
        
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
    }
}
