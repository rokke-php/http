<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use ReflectionNamedType;
use ReflectionParameter;
use Rokke\Runtime\Build\ArgumentSourceCompilerInterface;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;

final class BodyArgumentSourceCompiler implements ArgumentSourceCompilerInterface
{
	public function compile(ReflectionParameter $param, FactoryRepository $factories): ?ArgumentInstructionInterface
	{
		$type = $param->getType();

		if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
			return null;
		}

		/** @var class-string $typeName */
		$typeName = $type->getName();

		if (is_a($typeName, OperationContextInterface::class, true)) {
			return null;
		}

		if ($factories->get($typeName) !== null) {
			return null;
		}

		return new BodyInstruction($typeName);
	}
}
