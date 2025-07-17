<?php declare(strict_types=1);

namespace Tests\Az\Session;

use Az\Session\Session;
use Az\Session\Driver\ArrayDriver;
use Az\Session\SessionInterface;
use Az\Session\SessionMiddleware;
use Sys\Pipeline\Pipeline;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use HttpSoft\Response\TextResponse;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Runner\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;
use Closure;

final class SessionMiddlewareTest extends TestCase
{
    private Session $session;
    private RequestHandlerInterface $handler;
    private Pipeline $pipeline;

    public function setUp(): void
    {
        if (!defined('STORAGE')) {
            define('STORAGE', '');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $this->session = new Session(null, new ArrayDriver);
        $this->handler = $this->requestHanler(function ($request) {
            $session = $request->getAttribute('session');
            return ($session instanceof SessionInterface) ? 'true' : 'false';
        });

        $container = $this->createStub(ContainerInterface::class);
        $this->pipeline = new Pipeline($container);
    }

    public function testProcess()
    {
        $this->pipeline->pipe(new SessionMiddleware($this->session));
        $response = $this->pipeline->process(new ServerRequest(), $this->handler);

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
