<?php declare(strict_types=1);

namespace Tests\Az\Session;

use Az\Session\Session;
use Az\Session\Driver\ArrayDriver;
use Tests\Az\Session\Deps\RequestHandler;
use HttpSoft\Runner\MiddlewarePipeline;
use Psr\Http\Message\ServerRequestInterface;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery;

final class SessionGuardMiddlewareTest extends MockeryTestCase
{
    private RequestHandler $handler;
    private MiddlewarePipeline $pipeline;
    private ServerRequestInterface $request;

    public function setUp(): void
    {
        if (!defined('STORAGE')) {
            define('STORAGE', '');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SERVER['HTTP_USER_AGENT'] = 'user_agent';

        $session = new Session(['guard_agent' => true], new ArrayDriver);
        $this->handler = new RequestHandler(function ($request) {
            $request_ua = $request->getServerParams()['HTTP_USER_AGENT'];
            $session_ua = $request->getAttribute('session')->_user_agent;

            return ($session_ua === $request_ua) ? 'true' : 'false';
        });

        $this->pipeline = new MiddlewarePipeline();

        $this->request = Mockery::mock('request', ServerRequestInterface::class);
        $this->request->shouldReceive('getAttribute')
            ->with('session')
            ->andReturn($session);

        $this->assertInstanceOf(ServerRequestInterface::class, $this->request);
    }

    public function testProcessFalse()
    {
        $params = ['HTTP_USER_AGENT' => 'user_agent_fake',];
        $this->request->shouldReceive('getServerParams')
            ->andReturn($params);
        $response = $this->pipeline->process($this->request, $this->handler);
        $this->assertSame('false', $response->getBody()->getContents());       
    }

    public function testProcessTrue()
    {
        $params = ['HTTP_USER_AGENT' => 'user_agent',];
        $this->request->shouldReceive('getServerParams')
            ->andReturn($params);
        $response = $this->pipeline->process($this->request, $this->handler);
        $this->assertSame('true', $response->getBody()->getContents());
    }
}
