<?php declare(strict_types=1);

namespace Tests\Az\Session;

use Az\Session\Session;
use Az\Session\Driver\ArrayDriver;
use Az\Session\SessionInterface;
use Az\Session\SessionMiddleware;
use Tests\Az\Session\Deps\RequestHandler;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Runner\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;

final class SessionMiddlewareTest extends TestCase
{
    private Session $session;
    private RequestHandler $handler;
    private MiddlewarePipeline $pipeline;

    public function setUp(): void
    {
        if (!defined('STORAGE')) {
            define('STORAGE', '');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $this->session = new Session(null, new ArrayDriver);
        $this->handler = new RequestHandler(function ($request) {
            $session = $request->getAttribute('session');
            return ($session instanceof SessionInterface) ? 'true' : 'false';
        });

        $this->pipeline = new MiddlewarePipeline();
    }

    public function testProcess()
    {
        $this->pipeline->pipe(new SessionMiddleware($this->session));
        $response = $this->pipeline->process(new ServerRequest(), $this->handler);

        $this->assertSame('true', $response->getBody()->getContents());
    }
}
