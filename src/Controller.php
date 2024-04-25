<?php

namespace Whitelister;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Controller
{
	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	function get(ServerRequestInterface $request): ResponseInterface;

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	function post(ServerRequestInterface $request): ResponseInterface;

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	function put(ServerRequestInterface $request): ResponseInterface;

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	function delete(ServerRequestInterface $request): ResponseInterface;
}