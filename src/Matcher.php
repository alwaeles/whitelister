<?php

namespace Whitelister;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Matcher implements RequestHandlerInterface
{
	/**
	 * @var array An associative array of path regexp to controller pairs.
	 */
	private array $controllers;

	/**
	 * @var ResponseFactoryInterface A {@link ResponseInterface} factory to provide 40x-error responses if needed.
	 */
	private ResponseFactoryInterface $responseFactory;

	/**
	 * @var StreamFactoryInterface A {@link StreamInterface} factory to provide 40x-error bodies if needed.
	 */
	private StreamFactoryInterface $streamFactory;

	/**
	 * Constructs a {@link Matcher} instance.
	 *
	 * @param ContainerInterface $container A PSR-11 container implementation.
	 * @param array $controllers An associative array of path regexp to controller pairs.
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(ContainerInterface $container, array $controllers = [])
	{
		$this->controllers = $controllers;
		$this->responseFactory = $container->get(ResponseFactoryInterface::class);
		$this->streamFactory = $container->get(StreamFactoryInterface::class);
	}

	/**
	 * Adds another {@link Controller} to the matching list.
	 *
	 * @param string $path A PCRE compatible regular expression to match this controller entry.
	 * @param Controller $controller A Controller implementation
	 */
	public function add(string $path, Controller $controller): void
	{
		$this->controllers[$path] = $controller;
	}

	/**
	 * Proceeds to a route matching using the previously set up list of controller. You can provide this list at
	 * construction or use {@link Matcher::add()} to add more controller on the fly.
	 *
	 * @param ServerRequestInterface $request The request to match to a {@link Controller}.
	 * @return ResponseInterface The response to be sent back to the user.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		foreach ($this->controllers as $path => $controller) {
			if (preg_match($path, $request->getUri()->getPath()) === 1) {
				return match ($request->getMethod()) {
					'GET' => $controller->get($request),
					'POST' => $controller->post($request),
					'PUT' => $controller->put($request),
					'DELETE' => $controller->delete($request),
					default => $this->responseFactory->createResponse(405)
						->withHeader('Content-Type', 'text/html')
						->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'))
				};
			}
		}
		return $this->responseFactory->createResponse(404)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/404.html'));
	}
}