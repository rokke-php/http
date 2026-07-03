<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Compiled;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Compiled\CompiledRoute;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\Compiled\RouteMatch;

final class CompiledRouteTreeTest extends TestCase
{
	public function testEmptyTreeReturnsNullForAnyRequest(): void
	{
		$tree = CompiledRouteTree::empty();

		$this->assertNull($tree->match('GET', '/users'));
	}

	public function testMatchesStaticRoute(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users$#', 'users.list'),
		]);

		$match = $tree->match('GET', '/users');

		$this->assertInstanceOf(RouteMatch::class, $match);
	}

	public function testStaticRouteMatchHasNoParams(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users$#', 'users.list'),
		]);

		$match = $tree->match('GET', '/users');

		$this->assertNotNull($match);
		$this->assertSame([], $match->params);
	}

	public function testMatchReturnsCorrectOperationId(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users$#', 'users.list'),
		]);

		$match = $tree->match('GET', '/users');

		$this->assertNotNull($match);
		$this->assertSame('users.list', $match->operationId);
	}

	public function testMatchesSingleParam(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users/(?P<id>[^/]+)$#', 'users.show'),
		]);

		$match = $tree->match('GET', '/users/42');

		$this->assertNotNull($match);
		$this->assertSame('users.show', $match->operationId);
		$this->assertSame('42', $match->params['id']);
	}

	public function testMatchesMultipleParams(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/posts/(?P<postId>[^/]+)/comments/(?P<commentId>[^/]+)$#', 'comments.show'),
		]);

		$match = $tree->match('GET', '/posts/7/comments/3');

		$this->assertNotNull($match);
		$this->assertSame('7', $match->params['postId']);
		$this->assertSame('3', $match->params['commentId']);
	}

	public function testMethodMatchIsCaseInsensitive(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/ping$#', 'ping'),
		]);

		$this->assertNotNull($tree->match('get', '/ping'));
		$this->assertNotNull($tree->match('GET', '/ping'));
		$this->assertNotNull($tree->match('Get', '/ping'));
	}

	public function testWrongMethodReturnsNull(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users$#', 'users.list'),
		]);

		$this->assertNull($tree->match('POST', '/users'));
	}

	public function testUnknownPathReturnsNull(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users$#', 'users.list'),
		]);

		$this->assertNull($tree->match('GET', '/posts'));
	}

	public function testParamDoesNotMatchSlash(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users/(?P<id>[^/]+)$#', 'users.show'),
		]);

		// /users/42/extra should not match /users/{id}
		$this->assertNull($tree->match('GET', '/users/42/extra'));
	}

	public function testFirstMatchingRouteWins(): void
	{
		$tree = CompiledRouteTree::build([
			new CompiledRoute('GET', '#^/users/(?P<id>[^/]+)$#', 'users.show'),
			new CompiledRoute('GET', '#^/users/me$#', 'users.me'),
		]);

		$match = $tree->match('GET', '/users/me');

		$this->assertNotNull($match);
		// first registered route wins
		$this->assertSame('users.show', $match->operationId);
	}
}
