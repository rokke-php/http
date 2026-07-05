<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

final readonly class UserDto
{
	public function __construct(
		public int $id,
		public string $name,
	) {}
}
