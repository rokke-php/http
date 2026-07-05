<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Post;
use Rokke\Runtime\Contracts\OperationContextInterface;

#[Post('/users')]
final class CreateUserHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'created';
	}
}
