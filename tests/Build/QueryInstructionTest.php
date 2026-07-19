<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\QueryInstruction;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Context\OperationContext;

final class QueryInstructionTest extends TestCase
{
	/** @param array<string, string> $query */
	private function context(array $query): OperationContext
	{
		return new OperationContext('test', ['params' => [], 'headers' => [], 'body' => '', 'query' => $query]);
	}

	public function testResolvesStringValue(): void
	{
		$instruction = new QueryInstruction('term', 'string', nullable: false);
		$ctx         = $this->context(['term' => 'hello']);

		$this->assertSame('hello', $instruction->resolve($ctx, FactoryRepository::empty()));
	}

	public function testCastsToInt(): void
	{
		$instruction = new QueryInstruction('page', 'int', nullable: false);
		$ctx         = $this->context(['page' => '3']);

		$this->assertSame(3, $instruction->resolve($ctx, FactoryRepository::empty()));
	}

	public function testCastsToFloat(): void
	{
		$instruction = new QueryInstruction('ratio', 'float', nullable: false);
		$ctx         = $this->context(['ratio' => '1.5']);

		$this->assertSame(1.5, $instruction->resolve($ctx, FactoryRepository::empty()));
	}

	public function testCastsToBool(): void
	{
		$instruction = new QueryInstruction('active', 'bool', nullable: false);
		$ctx         = $this->context(['active' => 'true']);

		$this->assertTrue($instruction->resolve($ctx, FactoryRepository::empty()));
	}

	public function testNullableReturnNullWhenAbsent(): void
	{
		$instruction = new QueryInstruction('q', 'string', nullable: true);
		$ctx         = $this->context([]);

		$this->assertNull($instruction->resolve($ctx, FactoryRepository::empty()));
	}

	public function testRequiredThrowsWhenAbsent(): void
	{
		$instruction = new QueryInstruction('required', 'string', nullable: false);
		$ctx         = $this->context([]);

		$this->expectException(\RuntimeException::class);
		$instruction->resolve($ctx, FactoryRepository::empty());
	}

	public function testUsesCustomKeyName(): void
	{
		$instruction = new QueryInstruction('per_page', 'int', nullable: false);
		$ctx         = $this->context(['per_page' => '20']);

		$this->assertSame(20, $instruction->resolve($ctx, FactoryRepository::empty()));
	}
}
