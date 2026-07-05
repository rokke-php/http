<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\HeaderInstruction;
use Rokke\Runtime\Context\OperationContext;

final class HeaderInstructionTest extends TestCase
{
	/** @param array<string, string> $headers */
	private function context(array $headers): OperationContext
	{
		return new OperationContext('test', ['params' => [], 'headers' => $headers, 'body' => '', 'query' => []]);
	}

	public function testResolvesHeaderByExactName(): void
	{
		$instruction = new HeaderInstruction('X-Value', nullable: false);
		$ctx         = $this->context(['x-value' => 'hello']);

		$this->assertSame('hello', $instruction->resolve($ctx));
	}

	public function testHeaderLookupIsCaseInsensitive(): void
	{
		$instruction = new HeaderInstruction('Authorization', nullable: false);
		$ctx         = $this->context(['authorization' => 'Bearer token123']);

		$this->assertSame('Bearer token123', $instruction->resolve($ctx));
	}

	public function testNullableHeaderReturnNullWhenAbsent(): void
	{
		$instruction = new HeaderInstruction('X-Missing', nullable: true);
		$ctx         = $this->context([]);

		$this->assertNull($instruction->resolve($ctx));
	}

	public function testRequiredHeaderThrowsWhenAbsent(): void
	{
		$instruction = new HeaderInstruction('X-Required', nullable: false);
		$ctx         = $this->context([]);

		$this->expectException(\RuntimeException::class);
		$instruction->resolve($ctx);
	}
}
