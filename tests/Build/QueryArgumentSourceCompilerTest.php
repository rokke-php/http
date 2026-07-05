<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Rokke\Http\Attribute\Query;
use Rokke\Http\Build\QueryArgumentSourceCompiler;
use Rokke\Http\Build\QueryInstruction;
use Rokke\Runtime\Build\FactoryRepository;

final class QueryArgumentSourceCompilerTest extends TestCase
{
	private QueryArgumentSourceCompiler $compiler;
	private FactoryRepository $emptyRepo;

	protected function setUp(): void
	{
		$this->compiler  = new QueryArgumentSourceCompiler();
		$this->emptyRepo = FactoryRepository::empty();
	}

	public function testReturnsNullWithoutAttribute(): void
	{
		$refl  = new ReflectionFunction(static fn (string $x) => null);
		$param = $refl->getParameters()[0];

		$this->assertNull($this->compiler->compile($param, $this->emptyRepo));
	}

	public function testReturnsQueryInstructionForAttributedParam(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Query] string $term) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(QueryInstruction::class, $result);
	}

	public function testUsesParamNameAsKeyByDefault(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Query] int $page) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(QueryInstruction::class, $result);
	}

	public function testUsesCustomKeyFromAttribute(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Query('per_page')] int $limit) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(QueryInstruction::class, $result);
	}

	public function testNullableParamProducesNullableInstruction(): void
	{
		$refl  = new ReflectionFunction(static fn (#[Query] ?string $q) => null);
		$param = $refl->getParameters()[0];

		$result = $this->compiler->compile($param, $this->emptyRepo);

		$this->assertInstanceOf(QueryInstruction::class, $result);
	}
}
