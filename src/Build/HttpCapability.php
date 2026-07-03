<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Contracts\Module\CapabilityInterface;

final readonly class HttpCapability implements CapabilityInterface
{
	public function __construct(
		public string $method,
		public string $path,
		public string $operationId,
	) {}
}
