<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\RouteBuildPass;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Compiled\CompiledRouteTree;

final class RouteBuildPassTest extends TestCase
{
	private RouteBuildPass $compiler;

	protected function setUp(): void
	{
		$this->compiler = new RouteBuildPass();
	}

	public function testReturnsCompiledRouteTree(): void
	{
		$tree = $this->compiler->compile([]);

		$this->assertInstanceOf(CompiledRouteTree::class, $tree);
	}

	public function testEmptyListCompilesEmpty(): void
	{
		$tree = $this->compiler->compile([]);

		$this->assertNull($tree->match('GET', '/anything'));
	}

	public function testCompilesStaticRoute(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('GET', '/ping', 'ping'),
		]);

		$match = $tree->match('GET', '/ping');

		$this->assertNotNull($match);
		$this->assertSame('ping', $match->operationId);
	}

	public function testStaticRouteHasNoParams(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('GET', '/users', 'users.list'),
		]);

		$match = $tree->match('GET', '/users');

		$this->assertNotNull($match);
		$this->assertSame([], $match->params);
	}

	public function testCompilesParameterizedRoute(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('GET', '/users/{id}', 'users.show'),
		]);

		$match = $tree->match('GET', '/users/42');

		$this->assertNotNull($match);
		$this->assertSame('42', $match->params['id']);
	}

	public function testCompilesMultipleParams(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('GET', '/posts/{postId}/comments/{commentId}', 'comments.show'),
		]);

		$match = $tree->match('GET', '/posts/7/comments/3');

		$this->assertNotNull($match);
		$this->assertSame('7', $match->params['postId']);
		$this->assertSame('3', $match->params['commentId']);
	}

	public function testPreservesMethod(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('DELETE', '/users/{id}', 'users.delete'),
		]);

		$this->assertNull($tree->match('GET', '/users/1'));
		$this->assertNotNull($tree->match('DELETE', '/users/1'));
	}

	public function testPreservesOperationId(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('POST', '/users', 'users.create'),
		]);

		$match = $tree->match('POST', '/users');

		$this->assertNotNull($match);
		$this->assertSame('users.create', $match->operationId);
	}

	public function testCompilesMultipleRoutes(): void
	{
		$tree = $this->compiler->compile([
			new RouteDescriptor('GET', '/users', 'users.list'),
			new RouteDescriptor('POST', '/users', 'users.create'),
			new RouteDescriptor('GET', '/users/{id}', 'users.show'),
			new RouteDescriptor('DELETE', '/users/{id}', 'users.delete'),
		]);

		$this->assertSame('users.list', $tree->match('GET', '/users')?->operationId);
		$this->assertSame('users.create', $tree->match('POST', '/users')?->operationId);
		$this->assertSame('users.show', $tree->match('GET', '/users/99')?->operationId);
		$this->assertSame('users.delete', $tree->match('DELETE', '/users/99')?->operationId);
	}
}
