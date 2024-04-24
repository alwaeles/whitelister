<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Whitelister\Matcher;
use Whitelister\TwigController;


$factory = new Psr17Factory();
$creator = new ServerRequestCreator(
	$factory,
	$factory,
	$factory,
	$factory
);

$twig = new Environment(new FilesystemLoader(__DIR__ . '/../templates'), ['cache' => __DIR__ . '/../var/cache/twig']);

$matcher = new Matcher($factory, $factory);
$matcher->add('#^/$#', new TwigController($twig, $factory, $factory, 'startpage.html.twig'));

$request = $creator->fromGlobals();

$response = $matcher->handle($request);

(new SapiEmitter())->emit($response);
