<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Session;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        // Clean session before each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testSetAndGet(): void
    {
        Session::set('test_key', 'test_value');
        
        $this->assertEquals('test_value', Session::get('test_key'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', Session::get('nonexistent', 'default'));
    }

    public function testHas(): void
    {
        Session::set('test_key', 'test_value');
        
        $this->assertTrue(Session::has('test_key'));
        $this->assertFalse(Session::has('nonexistent'));
    }

    public function testDelete(): void
    {
        Session::set('test_key', 'test_value');
        Session::delete('test_key');
        
        $this->assertFalse(Session::has('test_key'));
    }
}
