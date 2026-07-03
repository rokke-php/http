<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Runtime\Contracts\OperationContextInterface;

#[Get('/ping')]
final class PingHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'pong';
	}
}
