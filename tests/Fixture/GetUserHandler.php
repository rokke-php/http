<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Runtime\Contracts\OperationContextInterface;

#[Get('/users/{id}')]
final class GetUserHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		$params = $ctx->metadata('params');

		if (!is_array($params)) {
			return '';
		}

		$id = $params['id'] ?? null;

		return is_string($id) ? $id : '';
	}
}
