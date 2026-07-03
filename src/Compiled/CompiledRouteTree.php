<?php

declare(strict_types=1);

namespace Rokke\Http\Compiled;

final class CompiledRouteTree
{
	/** @param list<CompiledRoute> $routes */
	private function __construct(private readonly array $routes) {}

	public static function empty(): self
	{
		return new self([]);
	}

	/** @param list<CompiledRoute> $routes */
	public static function build(array $routes): self
	{
		return new self($routes);
	}

	public function match(string $method, string $path): ?RouteMatch
	{
		$method = strtoupper($method);

		foreach ($this->routes as $route) {
			if ($route->method !== $method) {
				continue;
			}

			if (!preg_match($route->pattern, $path, $matches)) {
				continue;
			}

			$params = array_filter(
				$matches,
				static fn (mixed $key): bool => is_string($key),
				ARRAY_FILTER_USE_KEY,
			);

			return new RouteMatch($route->operationId, $params);
		}

		return null;
	}
}
