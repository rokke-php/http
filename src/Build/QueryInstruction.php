<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;

final readonly class QueryInstruction implements ArgumentInstructionInterface
{
	public function __construct(
		private string $key,
		private string $type,
		private bool $nullable,
	) {}

	public function resolve(OperationContextInterface $context, FactoryRepository $factories): mixed
	{
		/** @var array<string, string> $query */
		$query = $context->metadata('query') ?? [];
		$value = $query[$this->key] ?? null;

		if ($value === null && !$this->nullable) {
			throw new \RuntimeException(
				"Required query parameter '{$this->key}' is missing from the request.",
			);
		}

		if ($value === null) {
			return null;
		}

		return match ($this->type) {
			'int'   => (int) $value,
			'float' => (float) $value,
			'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN),
			default => (string) $value,
		};
	}
}
