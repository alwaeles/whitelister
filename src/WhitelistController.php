<?php

namespace Whitelister;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Session\SessionMiddleware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Wohali\OAuth2\Client\Provider\Discord;

class WhitelistController implements Controller
{

	private Environment $twig;

	private ResponseFactoryInterface $responseFactory;

	private StreamFactoryInterface $streamFactory;

	private Discord $provider;

	private ClientInterface $client;

	private RequestFactoryInterface $requestFactory;

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ContainerExceptionInterface
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->twig = $container->get(Environment::class);
		$this->responseFactory = $container->get(ResponseFactoryInterface::class);
		$this->streamFactory = $container->get(StreamFactoryInterface::class);
		$this->provider = $container->get(Discord::class);
		$this->client = $container->get(ClientInterface::class);
		$this->requestFactory = $container->get(RequestFactoryInterface::class);
	}


	/**
	 * @inheritDoc
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws ClientExceptionInterface
	 * @throws IdentityProviderException
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	function get(ServerRequestInterface $request): ResponseInterface
	{
		$session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
		if (!isset($request->getQueryParams()['code'])) {
			$url = $this->provider->getAuthorizationUrl([
				'scope' => ['identify', 'guilds.members.read']
			]);
			$session->set("authState", $this->provider->getState());
			return $this->responseFactory->createResponse(302)
				->withHeader('Location', $url);
		}

		if (empty($request->getQueryParams()['state']) ||
			$request->getQueryParams()['state'] !== $session->get("authState")) {
			return $this->responseFactory->createResponse(400);
		}

		$token = $this->provider->getAccessToken('authorization_code', [
			'code' => $request->getQueryParams()['code']
		]);

		$discordResponse = $this->client->sendRequest($this->requestFactory
			->createRequest('GET', 'users/@me/guilds/' . $_ENV['DISCORD_GUILD_ID'] . '/member')
			->withHeader('Authorization', 'Bearer ' . $token->getToken())
		);
		$guildMembership = json_decode($discordResponse->getBody()->getContents(), true);

		return $this->responseFactory->createResponse()
			->withHeader('Content-Type', 'text/html')
			->withBody($this->streamFactory->createStream($this->twig->render('form.html.twig', [
				'nickname' => $guildMembership['nick'],
				'csrf' => $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)->generateToken(),
			])));
	}

	/**
	 * @inheritDoc
	 */
	function post(ServerRequestInterface $request): ResponseInterface
	{
		try {
			return $this->responseFactory->createResponse(200)
				->withHeader('Content-Type', 'text/html')
				->withBody($this->streamFactory->createStream($this->twig->render('whitelist.html.twig')));
		} catch (LoaderError|RuntimeError|SyntaxError) {
			return $this->responseFactory->createResponse(500)
				->withHeader('Content-Type', 'text/html')
				->withBody($this->streamFactory->createStreamFromFile(__DIR__ . '/../htmlErrors/500.html'));
		}
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