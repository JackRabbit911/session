<?php

namespace Az\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Az\Session\Session;

class SessionGuardMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('session');

        if ($session->exists('_user_agent') && $session->_user_agent !== $request->getServerParams()['HTTP_USER_AGENT']) {
            $session->destroy(false);
        }

        if ($session->exists('_remote_addr') && $session->_remote_addr !== $request->getServerParams()['REMOTE_ADDR']) {
            $session->destroy(false);
        }

        return $handler->handle($request);
    }
}
