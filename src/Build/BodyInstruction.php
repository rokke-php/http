<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use ReflectionClass;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;

final class BodyInstruction implements ArgumentInstructionInterface
{
	/** @var class-string */
	private readonly string $class;

	/** @var list<string> Constructor parameter names, compiled at build time. */
	private readonly array $paramNames;

	/** @param class-string $class */
	public function __construct(string $class)
	{
		$this->class = $class;

		$reflection       = new ReflectionClass($class);
		$constructor      = $reflection->getConstructor();
		$this->paramNames = $constructor !== null
			? array_map(static fn (\ReflectionParameter $p): string => $p->getName(), $constructor->getParameters())
			: [];
	}

	public function resolve(OperationContextInterface $context): mixed
	{
		$raw  = $context->metadata('body');
		$data = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?? []) : [];

		/** @var array<string, mixed> $data */
		$args = [];

		foreach ($this->paramNames as $name) {
			$args[$name] = $data[$name] ?? null;
		}

		return new ($this->class)(...$args);
	}
}
