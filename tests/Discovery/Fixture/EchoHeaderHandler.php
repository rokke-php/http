<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Http\Attribute\Header;

#[Get('/header')]
final class EchoHeaderHandler
{
	public function __invoke(#[Header('X-Value')] string $value): string
	{
		return "header:{$value}";
	}
}
