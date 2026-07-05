<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Http\Attribute\Header;

#[Get('/optional-header')]
final class OptionalHeaderHandler
{
	public function __invoke(#[Header('X-Name')] ?string $name): string
	{
		return 'hello:' . ($name ?? 'world');
	}
}
