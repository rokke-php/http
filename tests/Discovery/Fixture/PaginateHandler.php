<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Http\Attribute\Query;

#[Get('/paginate')]
final class PaginateHandler
{
	public function __invoke(#[Query] int $page, #[Query('per_page')] int $limit): string
	{
		return "page:{$page},limit:{$limit}";
	}
}
