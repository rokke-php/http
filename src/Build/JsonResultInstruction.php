<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Runtime\Compiled\Results\ResultInstructionInterface;

final class JsonResultInstruction implements ResultInstructionInterface
{
	public function resolve(mixed $value): string
	{
		return json_encode($value, JSON_THROW_ON_ERROR);
	}
}
