<?php

declare(strict_types=1);

namespace Rokke\Http\Attribute;

abstract class HttpMethodAttribute
{
	public function __construct(public readonly string $path) {}

	abstract public function method(): string;
}
