<?php

/**
 * Copyright (c) 2010-2017 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Middleware\Controller;

use Eureka\Component\Controller\ControllerInterface;
use Eureka\Component\Psr\Http\Middleware\DelegateInterface;
use Eureka\Component\Psr\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message;

class ControllerMiddleware implements ServerMiddlewareInterface
{
    /**
     * @param Message\ServerRequestInterface  $request
     * @param DelegateInterface $frame
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(Message\ServerRequestInterface $request, DelegateInterface $frame)
    {
        if (null === $request->getAttribute('route')) {
            throw new \RuntimeException('Route not defined');
        }

        $response = $this->run($request);

        $otherResponse = $frame->next($request);
        $response->getBody()->write($otherResponse->getBody()->getContents());

        return $response;
    }

    /**
     * Run application middleware.
     *
     * @param  Message\ServerRequestInterface $request
     * @return Message\ResponseInterface
     */
    private function run(Message\ServerRequestInterface $request)
    {
        $route = $request->getAttribute('route');

        $controller = $route->getControllerName();
        $action     = $route->getActionName();

        if (!class_exists($controller)) {
            throw new \DomainException('Controller does not exists! (controller: ' . $controller . ')');
        }

        $controller = new $controller($route, $request);

        if (!($controller instanceof ControllerInterface)) {
            throw new \LogicException('Controller does not implement Controller Interface! (controller: ' . get_class($controller) . ')');
        }

        if (!method_exists($controller, $action)) {
            throw new \DomainException('Action controller does not exists! (' . get_class($controller) . '::' . $action);
        }

        $controller->runBefore();
        $response = $controller->$action();
        $controller->runAfter();

        return $response;
    }
}