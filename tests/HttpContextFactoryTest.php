<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Compiled\RouteMatch;
use Rokke\Http\HttpContextFactory;
use Rokke\Runtime\Context\OperationContext;

final class HttpContextFactoryTest extends TestCase
{
	private HttpContextFactory $factory;

	protected function setUp(): void
	{
		$this->factory = new HttpContextFactory();
	}

	public function testReturnsOperationContext(): void
	{
		$match = new RouteMatch('ping', []);

		$this->assertInstanceOf(OperationContext::class, $this->factory->fromMatch($match));
	}

	public function testPathParamsStoredUnderParamsKey(): void
	{
		$match = new RouteMatch('users.show', ['id' => '42']);

		$ctx = $this->factory->fromMatch($match);

		$this->assertSame(['id' => '42'], $ctx->metadata('params'));
	}

	public function testEmptyParamsForStaticRoute(): void
	{
		$match = new RouteMatch('ping', []);

		$ctx = $this->factory->fromMatch($match);

		$this->assertSame([], $ctx->metadata('params'));
	}

	public function testMultiplePathParams(): void
	{
		$match = new RouteMatch('comments.show', ['postId' => '7', 'commentId' => '3']);

		$ctx = $this->factory->fromMatch($match);

		$this->assertSame(['postId' => '7', 'commentId' => '3'], $ctx->metadata('params'));
	}

	public function testQueryParamsStoredUnderQueryKey(): void
	{
		$match = new RouteMatch('users.list', []);

		$ctx = $this->factory->fromMatch($match, query: ['page' => '2', 'limit' => '10']);

		$this->assertSame(['page' => '2', 'limit' => '10'], $ctx->metadata('query'));
	}

	public function testDefaultQueryIsEmptyArray(): void
	{
		$match = new RouteMatch('ping', []);

		$ctx = $this->factory->fromMatch($match);

		$this->assertSame([], $ctx->metadata('query'));
	}

	public function testHeadersStoredUnderHeadersKey(): void
	{
		$match = new RouteMatch('users.show', ['id' => '1']);

		$ctx = $this->factory->fromMatch($match, headers: ['content-type' => 'application/json']);

		$this->assertSame(['content-type' => 'application/json'], $ctx->metadata('headers'));
	}

	public function testDefaultHeadersIsEmptyArray(): void
	{
		$match = new RouteMatch('ping', []);

		$ctx = $this->factory->fromMatch($match);

		$this->assertSame([], $ctx->metadata('headers'));
	}

	public function testBodyStoredUnderBodyKey(): void
	{
		$match = new RouteMatch('users.create', []);

		$ctx = $this->factory->fromMatch($match, body: '{"name":"Alice"}');

		$this->assertSame('{"name":"Alice"}', $ctx->metadata('body'));
	}

	public function testDefaultBodyIsEmptyString(): void
	{
		$match = new RouteMatch('ping', []);

		$ctx = $this->factory->fromMatch($match);

		$this->assertSame('', $ctx->metadata('body'));
	}

	public function testEachCallProducesUniqueContextId(): void
	{
		$match = new RouteMatch('ping', []);

		$ctx1 = $this->factory->fromMatch($match);
		$ctx2 = $this->factory->fromMatch($match);

		$this->assertNotSame($ctx1->id(), $ctx2->id());
	}
}
