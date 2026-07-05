<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Rokke\Http\Build\JsonResultInstruction;
use Rokke\Http\Build\JsonResultSourceCompiler;
use Rokke\Http\Tests\Discovery\Fixture\UserDto;
use Rokke\Runtime\Context\OperationContext;

final class JsonResultSourceCompilerTest extends TestCase
{
	private JsonResultSourceCompiler $compiler;

	protected function setUp(): void
	{
		$this->compiler = new JsonResultSourceCompiler();
	}

	private function returnType(callable $fn): \ReflectionNamedType
	{
		$refl = new ReflectionFunction(\Closure::fromCallable($fn));
		$type = $refl->getReturnType();
		assert($type instanceof \ReflectionNamedType);

		return $type;
	}

	public function testReturnsNullForStringReturnType(): void
	{
		$this->assertNull($this->compiler->compile($this->returnType(static fn (): string => '')));
	}

	public function testReturnsNullForIntReturnType(): void
	{
		$this->assertNull($this->compiler->compile($this->returnType(static fn (): int => 0)));
	}

	public function testReturnsNullForArrayReturnType(): void
	{
		$this->assertNull($this->compiler->compile($this->returnType(static fn (): array => [])));
	}

	public function testReturnsNullForVoidReturnType(): void
	{
		$this->assertNull($this->compiler->compile($this->returnType(static function (): void {})));
	}

	public function testReturnsNullForContextReturnType(): void
	{
		$type = $this->returnType(static fn (): OperationContext => new OperationContext('x'));
		$this->assertNull($this->compiler->compile($type));
	}

	public function testReturnsJsonResultInstructionForDtoReturnType(): void
	{
		$type = $this->returnType(static fn (): UserDto => new UserDto(1, 'x'));
		$this->assertInstanceOf(JsonResultInstruction::class, $this->compiler->compile($type));
	}
}
