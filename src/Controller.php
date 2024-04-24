<?php

namespace Whitelister;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Controller
{
	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 */
	function get(RequestInterface $request): ResponseInterface;

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 */
	function post(RequestInterface $request): ResponseInterface;

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 */
	function put(RequestInterface $request): ResponseInterface;

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 */
	function delete(RequestInterface $request): ResponseInterface;
}