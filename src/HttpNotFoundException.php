<?php

declare(strict_types=1);

namespace Rokke\Http;

final class HttpNotFoundException extends \RuntimeException
{
	public function __construct(string $method, string $path)
	{
		parent::__construct("Route not found: {$method} {$path}");
	}
}
