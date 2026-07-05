<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

final readonly class CreateUserCommand
{
	public function __construct(
		public string $name,
		public string $email,
	) {}
}
