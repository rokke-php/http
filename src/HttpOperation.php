<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Runtime\Contracts\OperationInterface;

final readonly class HttpOperation implements OperationInterface
{
	public function __construct(private string $operationId) {}

	public function id(): string
	{
		return $this->operationId;
	}

	public function name(): string
	{
		return $this->operationId;
	}

	public function metadata(string $key, mixed $default = null): mixed
	{
		return $default;
	}
}
