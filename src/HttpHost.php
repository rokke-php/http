<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Engine\ExecutionEngine;
use Rokke\Runtime\Engine\Invoker;

final class HttpHost
{
	private readonly CompiledRouteTree $routeTree;
	private readonly ExecutionEngine $engine;
	private readonly HttpContextFactory $contextFactory;

	public function __construct(CompiledRuntime $runtime)
	{
		$this->routeTree      = $runtime->artifacts->get(CompiledRouteTree::class) ?? CompiledRouteTree::empty();
		$this->engine         = new ExecutionEngine(new Invoker($runtime));
		$this->contextFactory = new HttpContextFactory();
	}

	public function handle(string $method, string $path): mixed
	{
		$match = $this->routeTree->match($method, $path);

		if ($match === null) {
			throw new HttpNotFoundException($method, $path);
		}

		$operation = new HttpOperation($match->operationId);
		$context   = $this->contextFactory->fromMatch($match);

		return $this->engine->execute($operation, $context);
	}

	public function run(string $host, int $port): void
	{
		$server = new \Swoole\Http\Server($host, $port);

		$server->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response): void {
			try {
				$method = strtoupper($request->server['request_method'] ?? 'GET');
				$path   = $request->server['request_uri'] ?? '/';

				$match = $this->routeTree->match($method, $path);

				if ($match === null) {
					$response->status(404);
					$response->end('Not Found');

					return;
				}

				$operation = new HttpOperation($match->operationId);
				$context   = $this->contextFactory->fromRequest($request, $match);
				$result    = $this->engine->execute($operation, $context);
				$body      = is_string($result) ? $result : (string) json_encode($result);

				$response->end($body);
			} catch (\Throwable) {
				$response->status(500);
				$response->end('Internal Server Error');
			}
		});

		$server->start();
	}
}
