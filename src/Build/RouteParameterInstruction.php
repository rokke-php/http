<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;

final readonly class RouteParameterInstruction implements ArgumentInstructionInterface
{
	public function __construct(
		private string $name,
		private string $type,
	) {}

	public function resolve(OperationContextInterface $context): mixed
	{
		/** @var array<string, string> $params */
		$params = $context->metadata('params') ?? [];

		if (!array_key_exists($this->name, $params)) {
			throw new \RuntimeException(
				"Route parameter '{$this->name}' not found in context.",
			);
		}

		$raw = $params[$this->name];

		return match ($this->type) {
			'int'   => (int) $raw,
			'float' => (float) $raw,
			'bool'  => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
			default => (string) $raw,
		};
	}
}
