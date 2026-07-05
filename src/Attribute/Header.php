<?php

declare(strict_types=1);

namespace Rokke\Http\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Header
{
	public function __construct(public string $name) {}
}
