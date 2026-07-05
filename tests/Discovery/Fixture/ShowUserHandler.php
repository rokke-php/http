<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;

#[Get('/users/{id}')]
final class ShowUserHandler
{
	public function __invoke(int $id): string
	{
		return "user:{$id}";
	}
}
