<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Post;

#[Post('/users')]
final class CreateUserHandler
{
	public function __invoke(CreateUserCommand $command): string
	{
		return "created:{$command->name}";
	}
}
