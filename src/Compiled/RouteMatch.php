<?php

declare(strict_types=1);

namespace Rokke\Http\Compiled;

final readonly class RouteMatch
{
	/** @param array<string, string> $params */
	public function __construct(
		public string $operationId,
		public array $params,
	) {}
}
