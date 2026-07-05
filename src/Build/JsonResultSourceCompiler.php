<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use ReflectionNamedType;
use Rokke\Runtime\Build\ResultSourceCompilerInterface;
use Rokke\Runtime\Compiled\Results\ResultInstructionInterface;
use Rokke\Runtime\Contracts\OperationContextInterface;

final class JsonResultSourceCompiler implements ResultSourceCompilerInterface
{
	public function compile(ReflectionNamedType $type): ?ResultInstructionInterface
	{
		if ($type->isBuiltin()) {
			return null;
		}

		$name = $type->getName();

		if ($name === 'void' || $name === 'never') {
			return null;
		}

		if (is_a($name, OperationContextInterface::class, true)) {
			return null;
		}

		return new JsonResultInstruction();
	}
}
