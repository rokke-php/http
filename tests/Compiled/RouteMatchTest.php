<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Compiled;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Compiled\RouteMatch;

final class RouteMatchTest extends TestCase
{
	public function testHoldsOperationId(): void
	{
		$match = new RouteMatch('users.show', []);

		$this->assertSame('users.show', $match->operationId);
	}

	public function testHoldsEmptyParamsForStaticRoutes(): void
	{
		$match = new RouteMatch('ping', []);

		$this->assertSame([], $match->params);
	}

	public function testHoldsParams(): void
	{
		$match = new RouteMatch('users.show', ['id' => '42']);

		$this->assertSame(['id' => '42'], $match->params);
	}

	public function testHoldsMultipleParams(): void
	{
		$match = new RouteMatch('comments.show', ['postId' => '7', 'commentId' => '3']);

		$this->assertSame('7', $match->params['postId']);
		$this->assertSame('3', $match->params['commentId']);
	}
}
