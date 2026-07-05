<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use ReflectionNamedType;
use ReflectionParameter;
use Rokke\Http\Attribute\Header;
use Rokke\Runtime\Build\ArgumentSourceCompilerInterface;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentInstructionInterface;

final class HeaderArgumentSourceCompiler implements ArgumentSourceCompilerInterface
{
	public function compile(ReflectionParameter $param, FactoryRepository $factories): ?ArgumentInstructionInterface
	{
		$attrs = $param->getAttributes(Header::class);

		if ($attrs === []) {
			return null;
		}

		$headerName = $attrs[0]->newInstance()->name;
		$type       = $param->getType();
		$nullable   = !$type instanceof ReflectionNamedType || $type->allowsNull();

		return new HeaderInstruction($headerName, $nullable);
	}
}
