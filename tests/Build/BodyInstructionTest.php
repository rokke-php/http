<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\BodyInstruction;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Context\OperationContext;

// ── Fixtures ─────────────────────────────────────────────────────────────────

final readonly class BodyTestCommand
{
	public function __construct(
		public ?string $name,
		public ?string $email,
	) {}
}

final readonly class BodyTestCommandWithInt
{
	public function __construct(
		public string $name,
		public int $age,
	) {}
}

// ── Tests ─────────────────────────────────────────────────────────────────────

final class BodyInstructionTest extends TestCase
{
	private function context(string $body): OperationContext
	{
		return new OperationContext('test', ['body' => $body, 'params' => [], 'headers' => [], 'query' => []]);
	}

	public function testResolvesJsonBodyToDto(): void
	{
		$instruction = new BodyInstruction(BodyTestCommand::class);
		$ctx         = $this->context('{"name":"Fernando","email":"f@rokke.dev"}');

		$result = $instruction->resolve($ctx, FactoryRepository::empty());

		$this->assertInstanceOf(BodyTestCommand::class, $result);
		$this->assertSame('Fernando', $result->name);
		$this->assertSame('f@rokke.dev', $result->email);
	}

	public function testResolvesMixedTypesFromJson(): void
	{
		$instruction = new BodyInstruction(BodyTestCommandWithInt::class);
		$ctx         = $this->context('{"name":"Ana","age":28}');

		$result = $instruction->resolve($ctx, FactoryRepository::empty());

		$this->assertInstanceOf(BodyTestCommandWithInt::class, $result);
		$this->assertSame('Ana', $result->name);
		$this->assertSame(28, $result->age);
	}

	public function testExtraJsonFieldsAreIgnored(): void
	{
		$instruction = new BodyInstruction(BodyTestCommand::class);
		$ctx         = $this->context('{"name":"Fernando","email":"f@rokke.dev","extra":"ignored"}');

		$result = $instruction->resolve($ctx, FactoryRepository::empty());

		$this->assertInstanceOf(BodyTestCommand::class, $result);
		$this->assertSame('Fernando', $result->name);
	}

	public function testEmptyBodyYieldsNullFields(): void
	{
		$instruction = new BodyInstruction(BodyTestCommand::class);
		$ctx         = $this->context('{}');

		$result = $instruction->resolve($ctx, FactoryRepository::empty());

		$this->assertInstanceOf(BodyTestCommand::class, $result);
		$this->assertNull($result->name);
		$this->assertNull($result->email);
	}
}
