<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;
use Rokke\Http\Build\BodyArgumentSourceCompiler;
use Rokke\Http\Build\BodyInstruction;
use Rokke\Runtime\Build\FactoryCompiler;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Build\ServiceDescriptor;
use Rokke\Runtime\Contracts\OperationContextInterface;

final class BodyRegisteredService {}

final class BodyArgumentSourceCompilerTest extends TestCase
{
	private BodyArgumentSourceCompiler $compiler;
	private FactoryRepository $emptyRepo;
	private FactoryRepository $repoWithService;

	protected function setUp(): void
	{
		$this->compiler        = new BodyArgumentSourceCompiler();
		$this->emptyRepo       = FactoryRepository::empty();
		$this->repoWithService = FactoryRepository::build(
			[new ServiceDescriptor(BodyRegisteredService::class, BodyRegisteredService::class, [BodyRegisteredService::class])],
			new FactoryCompiler(),
		);
	}

	private function intParam(): ReflectionParameter
	{
		$refl = new ReflectionFunction(static fn (int $x) => null);

		return $refl->getParameters()[0];
	}

	private function stringParam(): ReflectionParameter
	{
		$refl = new ReflectionFunction(static fn (string $x) => null);

		return $refl->getParameters()[0];
	}

	private function contextParam(): ReflectionParameter
	{
		$refl = new ReflectionFunction(static fn (OperationContextInterface $x) => null);

		return $refl->getParameters()[0];
	}

	private function serviceParam(): ReflectionParameter
	{
		$refl = new ReflectionFunction(static fn (BodyRegisteredService $x) => null);

		return $refl->getParameters()[0];
	}

	public function testReturnsNullForIntType(): void
	{
		$this->assertNull($this->compiler->compile($this->intParam(), $this->emptyRepo));
	}

	public function testReturnsNullForStringType(): void
	{
		$this->assertNull($this->compiler->compile($this->stringParam(), $this->emptyRepo));
	}

	public function testReturnsNullForOperationContextInterface(): void
	{
		$this->assertNull($this->compiler->compile($this->contextParam(), $this->emptyRepo));
	}

	public function testReturnsNullForRegisteredService(): void
	{
		$this->assertNull($this->compiler->compile($this->serviceParam(), $this->repoWithService));
	}

	public function testReturnsBodyInstructionForUnregisteredClass(): void
	{
		$this->assertInstanceOf(BodyInstruction::class, $this->compiler->compile($this->serviceParam(), $this->emptyRepo));
	}
}
