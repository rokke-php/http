<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;

final readonly class HeaderInstruction implements ArgumentInstructionInterface
{
	public function __construct(
		private string $name,
		private bool $nullable,
	) {}

	public function resolve(OperationContextInterface $context): mixed
	{
		/** @var array<string, string> $headers */
		$headers = $context->metadata('headers') ?? [];

		$lower = strtolower($this->name);
		$value = $headers[$lower] ?? $headers[$this->name] ?? null;

		if ($value === null && !$this->nullable) {
			throw new \RuntimeException(
				"Required header '{$this->name}' is missing from the request.",
			);
		}

		return $value;
	}
}
