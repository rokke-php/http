<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;

#[Get('/profile/{id}')]
final class ShowUserDtoHandler
{
	public function __invoke(int $id): UserDto
	{
		return new UserDto($id, 'Fernando');
	}
}
