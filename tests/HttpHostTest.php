<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\RouteCompiler;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\HttpHost;
use Rokke\Http\HttpNotFoundException;
use Rokke\Runtime\Build\CompiledFactory;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Compiled\Arguments\ArgumentResolutionPlan;
use Rokke\Runtime\Compiled\Arguments\ContextArgumentInstruction;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledExecutionPipeline;
use Rokke\Runtime\Compiled\CompiledInterceptorPipeline;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Compiled\Results\ObjectResultInstruction;
use Rokke\Runtime\Compiled\Results\ResultResolutionPlan;
use Rokke\Runtime\Contracts\OperationContextInterface;

// ── Fixture handlers ──────────────────────────────────────────────────────────

final class HostPongHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'pong';
	}
}

final class HostListHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'list';
	}
}

final class HostCreatedHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'created';
	}
}

final class HostShowHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'show';
	}
}

final class HostDeletedHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		return 'deleted';
	}
}

final class HostIntResultHandler
{
	public function __invoke(OperationContextInterface $ctx): int
	{
		return 42;
	}
}

final class HostIdFromParamsHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		$params = $ctx->metadata('params');
		$id     = is_array($params) ? ($params['id'] ?? null) : null;

		return is_string($id) ? $id : '';
	}
}

final class HostPostCommentParamsHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		$params    = $ctx->metadata('params');
		$postId    = is_array($params) ? ($params['postId'] ?? null) : null;
		$commentId = is_array($params) ? ($params['commentId'] ?? null) : null;

		return (is_string($postId) ? $postId : '') . ':' . (is_string($commentId) ? $commentId : '');
	}
}

final class HostCheckEmptyParamsHandler
{
	public function __invoke(OperationContextInterface $ctx): string
	{
		$params = $ctx->metadata('params');

		return is_array($params) && $params === [] ? 'empty' : 'non-empty';
	}
}

// ── Test ──────────────────────────────────────────────────────────────────────

final class HttpHostTest extends TestCase
{
	/**
	 * @param array<string, array{method: string, path: string, handler: class-string}> $routes
	 *   Keys are operationIds; values describe the HTTP route and handler class.
	 */
	private function buildRuntime(array $routes): CompiledRuntime
	{
		$routeDescriptors = [];
		$factoryList      = [];
		$argumentPlans    = [];
		$resultPlans      = [];
		$compiledOps      = [];
		$i                = 0;

		foreach ($routes as $operationId => $route) {
			$routeDescriptors[] = new RouteDescriptor($route['method'], $route['path'], $operationId);
			$factoryList[$i]    = new CompiledFactory($route['handler']);
			$argumentPlans[$i]  = new ArgumentResolutionPlan([new ContextArgumentInstruction()]);
			$resultPlans[$i]    = new ResultResolutionPlan(new ObjectResultInstruction(\stdClass::class));
			$compiledOps[]      = new CompiledOperation(
				id: $operationId,
				pipelineId: 0,
				factoryId: $i,
				argumentPlanId: $i,
				resultPlanId: $i,
			);
			$i++;
		}

		$compiler  = new RouteCompiler();
		$routeTree = $compiler->compile($routeDescriptors);

		$executionPipeline = new CompiledExecutionPipeline(
			factories: FactoryRepository::fromDescriptors($factoryList),
			argumentPlans: $argumentPlans,
			resultPlans: $resultPlans,
			behaviorPipelines: [],
			validationPlans: [],
		);

		return new CompiledRuntime(
			executionPipeline: $executionPipeline,
			interceptorPipeline: CompiledInterceptorPipeline::empty(),
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
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => HostPongHandler::class],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('pong', $host->handle('GET', '/ping'));
	}

	public function testHandleThrowsForUnmatchedPath(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => HostPongHandler::class],
		]);

		$host = new HttpHost($runtime);

		$this->expectException(HttpNotFoundException::class);
		$host->handle('GET', '/missing');
	}

	public function testHandleThrowsForWrongMethod(): void
	{
		$runtime = $this->buildRuntime([
			'users.list' => ['method' => 'GET', 'path' => '/users', 'handler' => HostListHandler::class],
		]);

		$host = new HttpHost($runtime);

		$this->expectException(HttpNotFoundException::class);
		$host->handle('POST', '/users');
	}

	public function testHandleReturnsOperationResult(): void
	{
		$runtime = $this->buildRuntime([
			'users.create' => ['method' => 'POST', 'path' => '/users', 'handler' => HostIntResultHandler::class],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame(42, $host->handle('POST', '/users'));
	}

	public function testHandleRoutesCorrectOperationFromMany(): void
	{
		$runtime = $this->buildRuntime([
			'users.list'   => ['method' => 'GET',    'path' => '/users',      'handler' => HostListHandler::class],
			'users.create' => ['method' => 'POST',   'path' => '/users',      'handler' => HostCreatedHandler::class],
			'users.show'   => ['method' => 'GET',    'path' => '/users/{id}', 'handler' => HostShowHandler::class],
			'users.delete' => ['method' => 'DELETE', 'path' => '/users/{id}', 'handler' => HostDeletedHandler::class],
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
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => HostPongHandler::class],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('pong', $host->handle('get', '/ping'));
	}

	public function testHandleWithNoRouteTreeThrows404ForAnyRoute(): void
	{
		$runtime = new CompiledRuntime(
			executionPipeline: new CompiledExecutionPipeline(
				factories: FactoryRepository::empty(),
				argumentPlans: [],
				resultPlans: [],
				behaviorPipelines: [],
				validationPlans: [],
			),
			interceptorPipeline: CompiledInterceptorPipeline::empty(),
			artifacts: ArtifactRepository::empty(),
		);

		$host = new HttpHost($runtime);

		$this->expectException(HttpNotFoundException::class);
		$host->handle('GET', '/ping');
	}

	public function testHandlerReceivesSinglePathParam(): void
	{
		$runtime = $this->buildRuntime([
			'users.show' => [
				'method'  => 'GET',
				'path'    => '/users/{id}',
				'handler' => HostIdFromParamsHandler::class,
			],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('42', $host->handle('GET', '/users/42'));
	}

	public function testHandlerReceivesMultiplePathParams(): void
	{
		$runtime = $this->buildRuntime([
			'comments.show' => [
				'method'  => 'GET',
				'path'    => '/posts/{postId}/comments/{commentId}',
				'handler' => HostPostCommentParamsHandler::class,
			],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('7:3', $host->handle('GET', '/posts/7/comments/3'));
	}

	public function testHandlerReceivesEmptyParamsForStaticRoute(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => [
				'method'  => 'GET',
				'path'    => '/ping',
				'handler' => HostCheckEmptyParamsHandler::class,
			],
		]);

		$host = new HttpHost($runtime);

		$this->assertSame('empty', $host->handle('GET', '/ping'));
	}

	public function testHttpNotFoundExceptionCarriesMethodAndPath(): void
	{
		$runtime = $this->buildRuntime([
			'ping' => ['method' => 'GET', 'path' => '/ping', 'handler' => HostPongHandler::class],
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
