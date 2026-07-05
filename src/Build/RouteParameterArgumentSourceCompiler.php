<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use ReflectionNamedType;
use ReflectionParameter;
use Rokke\Runtime\Build\ArgumentSourceCompilerInterface;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;

final class RouteParameterArgumentSourceCompiler implements ArgumentSourceCompilerInterface
{
	private const SCALAR_TYPES = ['int', 'string', 'float', 'bool'];

	public function compile(ReflectionParameter $param, FactoryRepository $factories): ?ArgumentInstructionInterface
	{
		$type = $param->getType();

		if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
			return null;
		}

		if (!in_array($type->getName(), self::SCALAR_TYPES, true)) {
			return null;
		}

		return new RouteParameterInstruction($param->getName(), $type->getName());
	}
}
