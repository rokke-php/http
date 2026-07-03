<?php

declare(strict_types=1);

namespace Rokke\Http\Compiled;

final readonly class CompiledRoute
{
	public function __construct(
		public string $method,
		public string $pattern,
		public string $operationId,
	) {}
}
