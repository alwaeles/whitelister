<?php

namespace Whitelister;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigController implements Controller
{

	private Environment $twig;

	private ResponseFactoryInterface $responseFactory;
	private StreamFactoryInterface $streamFactory;

	private string $templateName;

	/**
	 * @param ContainerInterface $container
	 * @param string $templateName
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(ContainerInterface $container, string $templateName)
	{
		$this->twig = $container->get(Environment::class);
		$this->responseFactory = $container->get(ResponseFactoryInterface::class);
		$this->streamFactory = $container->get(StreamFactoryInterface::class);
		$this->templateName = $templateName;
	}


	/**
	 * @inheritDoc
	 */
	function get(ServerRequestInterface $request): ResponseInterface
	{
		try {
			return $this->responseFactory->createResponse(200)
				->withHeader('Content-Type', 'text/html')
				->withBody($this->streamFactory->createStream($this->twig->render($this->templateName)));
		} catch (LoaderError|RuntimeError|SyntaxError) {
			return $this->responseFactory->createResponse(500)
				->withHeader('Content-Type', 'text/html')
				->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/500.html'));
		}
	}

	/**
	 * @inheritDoc
	 */
	function post(ServerRequestInterface $request): ResponseInterface
	{
		return $this->responseFactory->createResponse(405)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'));
	}

	/**
	 * @inheritDoc
	 */
	function put(ServerRequestInterface $request): ResponseInterface
	{
		return $this->responseFactory->createResponse(405)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'));
	}

	/**
	 * @inheritDoc
	 */
	function delete(ServerRequestInterface $request): ResponseInterface
	{
		return $this->responseFactory->createResponse(405)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'));
	}
}