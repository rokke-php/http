<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Contracts\Build\DefinitionInterface;

final readonly class RouteDescriptor implements DefinitionInterface
{
	public function __construct(
		public string $method,
		public string $path,
		public string $operationId,
	) {}
}
