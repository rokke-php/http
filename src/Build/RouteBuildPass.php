<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Http\Compiled\CompiledRoute;
use Rokke\Http\Compiled\CompiledRouteTree;

final class RouteBuildPass
{
	/** @param list<RouteDescriptor> $descriptors */
	public function compile(array $descriptors): CompiledRouteTree
	{
		$routes = [];

		foreach ($descriptors as $descriptor) {
			$routes[] = new CompiledRoute(
				method: $descriptor->method,
				pattern: $this->buildPattern($descriptor->path),
				operationId: $descriptor->operationId,
			);
		}

		return CompiledRouteTree::build($routes);
	}

	private function buildPattern(string $path): string
	{
		$pattern = preg_replace_callback(
			'/\{(\w+)\}|([^{]+)/',
			static function (array $match): string {
				if ($match[1] !== '') {
					return '(?P<' . $match[1] . '>[^/]+)';
				}

				return preg_quote($match[2], '#');
			},
			$path,
		);

		return '#^' . $pattern . '$#';
	}
}
