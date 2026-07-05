<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\JsonResultInstruction;

final class JsonResultInstructionTest extends TestCase
{
	private JsonResultInstruction $instruction;

	protected function setUp(): void
	{
		$this->instruction = new JsonResultInstruction();
	}

	public function testSerializesObjectToJson(): void
	{
		$dto    = new \stdClass();
		$dto->id = 1;
		$dto->name = 'Fernando';

		$result = $this->instruction->resolve($dto);

		$this->assertIsString($result);
		$this->assertSame(['id' => 1, 'name' => 'Fernando'], json_decode($result, true));
	}

	public function testSerializesReadonlyClassToJson(): void
	{
		$obj    = new class (42, 'Rokke') {
			public function __construct(
				public readonly int $id,
				public readonly string $name,
			) {}
		};

		$result = $this->instruction->resolve($obj);

		$this->assertIsString($result);
		$decoded = json_decode($result, true);
		$this->assertIsArray($decoded);
		$this->assertSame(42, $decoded['id']);
		$this->assertSame('Rokke', $decoded['name']);
	}

	public function testSerializesNestedObject(): void
	{
		$obj         = new \stdClass();
		$obj->user   = new \stdClass();
		$obj->user->id = 7;

		$result  = $this->instruction->resolve($obj);
		$decoded = json_decode($result, true);
		$this->assertIsArray($decoded);
		$this->assertIsArray($decoded['user']);
		$this->assertSame(7, $decoded['user']['id']);
	}
}
