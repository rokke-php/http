<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Http\Attribute\Query;

#[Get('/search')]
final class SearchHandler
{
	public function __invoke(#[Query] string $term): string
	{
		return "search:{$term}";
	}
}
