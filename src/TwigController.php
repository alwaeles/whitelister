<?php

namespace Whitelister;

use Psr\Http\Message\RequestInterface;
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
	 * @param Environment $twig
	 * @param ResponseFactoryInterface $responseFactory
	 * @param StreamFactoryInterface $streamFactory
	 * @param string $templateName
	 */
	public function __construct(Environment $twig, ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, string $templateName)
	{
		$this->twig = $twig;
		$this->responseFactory = $responseFactory;
		$this->streamFactory = $streamFactory;
		$this->templateName = $templateName;
	}


	/**
	 * @inheritDoc
	 */
	function get(RequestInterface $request): ResponseInterface
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
	function post(RequestInterface $request): ResponseInterface
	{
		return $this->responseFactory->createResponse(405)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'));
	}

	/**
	 * @inheritDoc
	 */
	function put(RequestInterface $request): ResponseInterface
	{
		return $this->responseFactory->createResponse(405)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'));
	}

	/**
	 * @inheritDoc
	 */
	function delete(RequestInterface $request): ResponseInterface
	{
		return $this->responseFactory->createResponse(405)
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/405.html'));
	}
}