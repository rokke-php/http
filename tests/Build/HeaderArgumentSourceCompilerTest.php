<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Rokke\Http\Attribute\Header;
use Rokke\Http\Build\HeaderArgumentSourceCompiler;
use Rokke\Http\Build\HeaderInstruction;
use Rokke\Runtime\Build\FactoryRepository;

final class HeaderArgumentSourceCompilerTest extends TestCase
{
	private HeaderArgumentSourceCompiler $compiler;
	private FactoryRepository $emptyRepo;

	protected function setUp(): void
	{
		$this->compiler  = new HeaderArgumentSourceCompiler();
		$this->emptyRepo = FactoryRepository::empty();
	}

	public function testReturnsNullForParamWithoutHeaderAttribute(): void
	{
		$refl  = new ReflectionFunction(static fn (string $x) => null);
		$param = $refl->getParameters()[0];

		$this->assertNull($this->compiler->compile($param, $this->emptyRepo));
	}

	public function testReturnsHeaderInstructionForAttributedStringParam(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Header('X-Value')] string $x) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(HeaderInstruction::class, $result);
	}

	public function testReturnsHeaderInstructionForAttributedNullableParam(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Header('X-Name')] ?string $x) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(HeaderInstruction::class, $result);
	}

	public function testReturnsHeaderInstructionForAttributedClassParam(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Header('X-Data')] BodyRegisteredService $x) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(HeaderInstruction::class, $result);
	}
}
