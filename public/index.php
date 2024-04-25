<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as ClientBase;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use League\Container\Argument\Literal\ArrayArgument;
use League\Container\Argument\Literal\ObjectArgument;
use League\Container\Container;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Csrf\SessionCsrfGuardFactory;
use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Relay\Relay;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Whitelister\Matcher;
use Whitelister\TwigController;
use Whitelister\WhitelistController;
use Wohali\OAuth2\Client\Provider\Discord;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required([
	'DISCORD_CLIENT_ID',
	'DISCORD_CLIENT_SECRET',
	'DISCORD_REDIRECT_URI',
	'DISCORD_GUILD_ID',
	'DISCORD_ROLE_ID',
]);

$container = new Container();
$container->add(Psr17Factory::class);
$container->add(UriFactoryInterface::class, Psr17Factory::class);
$container->add(ServerRequestFactoryInterface::class, Psr17Factory::class);
$container->add(RequestFactoryInterface::class, Psr17Factory::class);
$container->add(ResponseFactoryInterface::class, Psr17Factory::class);
$container->add(UploadedFileFactoryInterface::class, Psr17Factory::class);
$container->add(StreamFactoryInterface::class, Psr17Factory::class);
$container->add(ClientInterface::class, Client::class)
	->addArgument(new ArrayArgument([
		'base_uri' => 'https://discord.com/api/v10/',
		'headers' => [
			'User-Agent' => 'DiscordBot (https://github.com/alwaeles/whitelister, 1.0) GuzzleHttp/'
				. ClientBase::MAJOR_VERSION,
		]
	]));
$container->add(ServerRequestCreator::class)
	->addArgument(Psr17Factory::class)
	->addArgument(Psr17Factory::class)
	->addArgument(Psr17Factory::class)
	->addArgument(Psr17Factory::class);
$container->add(Environment::class)
	->addArgument(new ObjectArgument(new FilesystemLoader(__DIR__ . '/../templates')))
	->addArgument(new ArrayArgument(['cache' => __DIR__ . '/../var/cache/twig']));
$container->add(Discord::class)
	->addArgument(new ArrayArgument([
		'clientId' => $_ENV['DISCORD_CLIENT_ID'],
		'clientSecret' => $_ENV['DISCORD_CLIENT_SECRET'],
		'redirectUri' => $_ENV['DISCORD_REDIRECT_URI'],
	]));

$container = $container->defaultToShared();

$emitter = new SapiEmitter();

try {
	$matcher = new Matcher($container);
	$matcher->add('#^/$#', new TwigController($container, 'startpage.html.twig'));
	$matcher->add('#^/next$#', new WhitelistController($container));

	$queue = [
		new SessionMiddleware(new PhpSessionPersistence()),
		new CsrfMiddleware(new SessionCsrfGuardFactory()),
		$matcher
	];

	$runner = new RequestHandlerRunner(
		new Relay($queue),
		$emitter,
		function () use ($container) {
			return $container->get(ServerRequestCreator::class)->fromGlobals();
		},
		function (Throwable $e) use ($container) {
			return $container->get(ResponseFactoryInterface::class)->createResponse(500)
				->withHeader('Content-Type', 'text/plain')
				->withBody($container->get(StreamFactoryInterface::class)->createStream($e));
		}
	);

	$runner->run();
} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
	$factory = new Psr17Factory();
	$emitter->emit($factory->createResponse(500)
		->withHeader('Content-Type', 'text/plain')
		->withBody($factory->createStream($e)));

}

