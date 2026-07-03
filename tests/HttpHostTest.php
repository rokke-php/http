<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\RouteCompiler;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\HttpHost;
use Rokke\Http\HttpNotFoundException;
use Rokke\Runtime\Compiled\Arguments\ArgumentResolutionPlan;
use Rokke\Runtime\Compiled\Arguments\ContextArgumentInstruction;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Compiled\Results\ObjectResultInstruction;
use Rokke\Runtime\Compiled\Results\ResultResolutionPlan;
use Rokke\Runtime\Contracts\OperationContextInterface;

final class HttpHostTest extends TestCase
{
	// ── Fixture ───────────────────────────────────────────────────────────────

	/**
	 * @param array<string, array{method: string, path: string, handler: callable}> $routes
	 *   Keys are operationIds; values describe the HTTP route and handler.
	 */
	private function buildRuntime(array $routes): CompiledRuntime
	{
		$descriptors  = [];
		$handlers     = [];
		$argumentPlans = [];
		$resultPlans  = [];
		$compiledOps  = [];
		$i            = 0;

		foreach ($routes as $operationId => $route) {
			$descriptors[]    = new RouteDescriptor($route['method'], $route['path'], $operationId);
			$handlers[$i]     = $route['handler'];
			$argumentPlans[$i] = new ArgumentResolutionPlan([new ContextArgumentInstruction()]);
			$resultPlans[$i]  = new ResultResolutionPlan(new ObjectResultInstruction(\stdClass::class));
			$compiledOps[]    = new CompiledOperation(
				id: $operationId,
				pipelineId: 0,
				handlerId: $i,
				argumentPlanId: $i,
				resultPlanId: $i,
			);
			$i++;
		}

		$compiler  = new RouteCompiler();
		$routeTree = $compiler->compile($descriptors);

		return new CompiledRuntime(
			pipelines: [],
			handlers: $handlers,
			argumentPlans: $argumentPlans,
			resultPlans: $resultPlans,
			operations: OperationRepository::build($compiledOps),
			artifacts: ArtifactRepository::build([
				CompiledRouteTree::class => $routeTree,
			]),
		);
	}

	// ── Tests ─────────────────────────────────────────────────────────────────

	public function testHandleDispatchesMatchedRoute(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => fn (OperationContextInterface $ctx): string => 'pong'],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('pong', $host->handle('GET', '/ping'));
	}

	public function testHandleThrowsForUnmatchedPath(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => fn (OperationContextInterface $ctx): string => 'pong'],
		]);

		$host = new HttpHost($runtime);

		$this->expectException(HttpNotFoundException::class);
		$host->handle('GET', '/missing');
	}

	public function testHandleThrowsForWrongMethod(): void
	{
		$runtime = $this->buildRuntime([
			'users.list' => ['method' => 'GET', 'path' => '/users', 'handler' => fn (OperationContextInterface $ctx): string => 'list'],
		]);

		$host = new HttpHost($runtime);

		$this->expectException(HttpNotFoundException::class);
		$host->handle('POST', '/users');
	}

	public function testHandleReturnsOperationResult(): void
	{
		$runtime = $this->buildRuntime([
			'users.create' => ['method' => 'POST', 'path' => '/users', 'handler' => fn (OperationContextInterface $ctx): int => 42],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame(42, $host->handle('POST', '/users'));
	}

	public function testHandleRoutesCorrectOperationFromMany(): void
	{
		$runtime = $this->buildRuntime([
			'users.list'   => ['method' => 'GET',    'path' => '/users',      'handler' => fn (OperationContextInterface $ctx): string => 'list'],
			'users.create' => ['method' => 'POST',   'path' => '/users',      'handler' => fn (OperationContextInterface $ctx): string => 'created'],
			'users.show'   => ['method' => 'GET',    'path' => '/users/{id}', 'handler' => fn (OperationContextInterface $ctx): string => 'show'],
			'users.delete' => ['method' => 'DELETE', 'path' => '/users/{id}', 'handler' => fn (OperationContextInterface $ctx): string => 'deleted'],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('list', $host->handle('GET', '/users'));
		$this->assertSame('created', $host->handle('POST', '/users'));
		$this->assertSame('show', $host->handle('GET', '/users/1'));
		$this->assertSame('deleted', $host->handle('DELETE', '/users/99'));
	}

	public function testHandleIsMethodCaseInsensitive(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => fn (OperationContextInterface $ctx): string => 'pong'],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('pong', $host->handle('get', '/ping'));
	}

	public function testHandleWithNoRouteTreeThrows404ForAnyRoute(): void
	{
		$runtime = new CompiledRuntime(
			pipelines: [],
			handlers: [],
			argumentPlans: [],
			resultPlans: [],
			artifacts: ArtifactRepository::empty(),
		);

		$host = new HttpHost($runtime);

		$this->expectException(HttpNotFoundException::class);
		$host->handle('GET', '/ping');
	}

	public function testHttpNotFoundExceptionCarriesMethodAndPath(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => fn (OperationContextInterface $ctx): string => 'pong'],
		]);

		$host = new HttpHost($runtime);

		try {
			$host->handle('DELETE', '/unknown');
			$this->fail('Expected HttpNotFoundException');
		} catch (HttpNotFoundException $e) {
			$this->assertStringContainsString('DELETE', $e->getMessage());
			$this->assertStringContainsString('/unknown', $e->getMessage());
		}
	}
}
