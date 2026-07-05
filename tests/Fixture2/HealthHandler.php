<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Fixture2;

use Rokke\Http\Attribute\Get;
use Rokke\Runtime\Contracts\OperationContextInterface;

#[Get('/health')]
final class HealthHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'ok';
	}
}
