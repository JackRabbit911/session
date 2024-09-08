<?php declare(strict_types=1);

namespace Tests\Az\Session;

use Az\Session\Session;
use Az\Session\Driver\ArrayDriver;

use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    private $session;

    public function setUp(): void
    {
        if (!defined('STORAGE')) {
            define('STORAGE', '');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $this->session = new Session(null, new ArrayDriver);
    }

    public function testGetCookieName()
    {
        $this->assertSame('SID', $this->session->getCookieName());
    }

    public function testId()
    {
        $this->assertSame('', $this->session->id());
    }

    public function testSetGet()
    {
        $this->session->foo = 'bar';
        $this->assertSame('bar', $this->session->foo);
        $this->assertSame('bar', $this->session->get('foo'));
        $this->session->set('foo', 'baz');
        $this->assertSame('baz', $this->session->foo);
        $this->assertTrue($this->session->exists('foo'));
        $this->assertNull($this->session->invalidKey);
    }

    public function testExists()
    {
        $this->assertFalse($this->session->exists('foo'));
        $this->session->foo = 'bar';
        $this->assertTrue($this->session->exists('foo'));
    }

    public function testPullDeleteRemove()
    {
        $this->session->foo = 'bar';
        $this->assertSame('bar', $this->session->pull('foo'));
        $this->assertSame('bar', $this->session->foo);
        $this->session->delete('foo');
        $this->assertSame('bar', $this->session->foo);
        $this->session->remove('foo');
        $this->assertNull($this->session->foo);
    }

    public function testCommit()
    {
        $this->session->foo = 'bar';
        $this->assertTrue($this->session->commit());
        $this->assertSame('bar', $this->session->foo);
    }

    public function testGc()
    {
        $this->assertFalse($this->session->gc());
    }

    public function testFlashKeep()
    {
        $this->session->flash('foo', 'baz');
        $this->assertSame('baz', $this->session->foo);
        $this->session->keep('foo');
        $this->assertSame('baz', $this->session->foo);
    }

    public function testIncrementDecrement()
    {
        $this->session->increment('foo');
        $this->assertSame(1, $this->session->foo);
        $this->session->increment('foo');
        $this->assertSame(2, $this->session->foo);
        $this->session->increment('foo', 10);
        $this->assertSame(12, $this->session->foo);
        $this->session->bar = 50;
        $this->session->increment('bar', 90);
        $this->assertSame(140, $this->session->bar);
        $this->session->decrement('bar');
        $this->assertSame(139, $this->session->bar);
        $this->session->decrement('bar', 140);
        $this->assertSame(-1, $this->session->bar);
    }

    public function testDestroy()
    {
        $this->session->foo = 'bar';
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->session->destroy(false);
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->session->destroy();
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testRegenerate()
    {
        $this->assertTrue($this->session->regenerate(true));
    }
}
