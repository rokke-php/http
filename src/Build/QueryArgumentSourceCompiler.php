<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use ReflectionNamedType;
use ReflectionParameter;
use Rokke\Http\Attribute\Query;
use Rokke\Runtime\Build\ArgumentSourceCompilerInterface;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;

final class QueryArgumentSourceCompiler implements ArgumentSourceCompilerInterface
{
	public function compile(ReflectionParameter $param, FactoryRepository $factories): ?ArgumentInstructionInterface
	{
		$attrs = $param->getAttributes(Query::class);

		if ($attrs === []) {
			return null;
		}

		$attr     = $attrs[0]->newInstance();
		$key      = $attr->name ?? $param->getName();
		$type     = $param->getType();
		$typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'string';
		$nullable = !$type instanceof ReflectionNamedType || $type->allowsNull();

		return new QueryInstruction($key, $typeName, $nullable);
	}
}
