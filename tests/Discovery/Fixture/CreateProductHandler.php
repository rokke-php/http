<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery\Fixture;

use Rokke\Http\Attribute\Get;
use Rokke\Http\Attribute\Query;
use Rokke\Runtime\Attribute\Max;
use Rokke\Runtime\Attribute\Min;
use Rokke\Runtime\Attribute\NotBlank;

#[Get('/products')]
final class CreateProductHandler
{
	public function __invoke(
		#[Query]
		#[NotBlank]
		string $name,
		#[Query]
		#[Min(1)]
		#[Max(9999)]
		int $price,
	): string {
		return "product:{$name}:{$price}";
	}
}
