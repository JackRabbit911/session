<?php declare(strict_types=1);

namespace Tests\Az\Session;

use Az\Session\Session;
use Az\Session\Driver\ArrayDriver;
use Sys\Pipeline\Pipeline;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HttpSoft\Response\TextResponse;
use PHPUnit\Framework\TestCase;
use Closure;

final class SessionGuardMiddlewareTest extends TestCase
{
    private RequestHandlerInterface $handler;
    private Pipeline $pipeline;
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

        $this->handler = $this->requestHanler(function ($request) {
            $request_ua = $request->getServerParams()['HTTP_USER_AGENT'];
            $session_ua = $request->getAttribute('session')->_user_agent;

            return ($session_ua === $request_ua) ? 'true' : 'false';
        });

        $container = $this->createStub(ContainerInterface::class);
        $this->pipeline = new Pipeline($container);

        $this->request = $this->createStub(ServerRequestInterface::class);
        $this->request->method('getAttribute')
            ->with('session')
            ->willReturn($session);

        $this->assertInstanceOf(ServerRequestInterface::class, $this->request);
    }

    public function testProcessFalse()
    {
        $params = ['HTTP_USER_AGENT' => 'user_agent_fake',];
        $this->request->method('getServerParams')
            ->willReturn($params);

        $response = $this->pipeline->process($this->request, $this->handler);
        $this->assertSame('false', $response->getBody()->getContents());       
    }

    public function testProcessTrue()
    {
        $params = ['HTTP_USER_AGENT' => 'user_agent',];

        $this->request->method('getServerParams')
            ->willReturn($params);

        $response = $this->pipeline->process($this->request, $this->handler);
        $this->assertSame('true', $response->getBody()->getContents());
    }

    private function requestHanler(Closure $callable): RequestHandlerInterface
    {
        return new class ($callable) implements RequestHandlerInterface {
            public function __construct(private $callable){}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $str = call_user_func($this->callable, $request);
                return (is_string($str)) ? new TextResponse($str) : $str;
            }
        };
    }
}
