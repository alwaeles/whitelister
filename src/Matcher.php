<?php

namespace Whitelister;

use Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
	 * @param array $controllers An associative array of path regexp to controller pairs.
	 */
	public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, array $controllers = [])
	{
		$this->controllers = $controllers;
		$this->responseFactory = $responseFactory;
		$this->streamFactory = $streamFactory;
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
	 * @param RequestInterface $request The request to match to a {@link Controller}.
	 * @return ResponseInterface the response to be sent back to the user.
	 */
	public function handle(RequestInterface $request): ResponseInterface
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