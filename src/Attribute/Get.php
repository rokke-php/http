<?php

declare(strict_types=1);

namespace Rokke\Http\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Get extends HttpMethodAttribute
{
	public function method(): string
	{
		return 'GET';
	}
}
