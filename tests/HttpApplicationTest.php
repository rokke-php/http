<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Http\HttpApplication;
use Rokke\Http\HttpNotFoundException;
use Rokke\Http\Tests\Fixture\CreateUserHandler;
use Rokke\Http\Tests\Fixture\GetUserHandler;
use Rokke\Http\Tests\Fixture\PingHandler;

final class HttpApplicationTest extends TestCase
{
	public function testRegisterReturnsFluentInstance(): void
	{
		$app = HttpApplication::create();

		$this->assertSame($app, $app->register(PingHandler::class));
	}

	public function testHandleDispatchesGetHandler(): void
	{
		$host = HttpApplication::create()
			->register(PingHandler::class)
			->build();

		$this->assertSame('pong', $host->handle('GET', '/ping'));
	}

	public function testHandleDispatchesPostHandler(): void
	{
		$host = HttpApplication::create()
			->register(CreateUserHandler::class)
			->build();

		$this->assertSame('created', $host->handle('POST', '/users'));
	}

	public function testHandleRouteWithPathParam(): void
	{
		$host = HttpApplication::create()
			->register(GetUserHandler::class)
			->build();

		$this->assertSame('42', $host->handle('GET', '/users/42'));
	}

	public function testHandleThrowsForUnregisteredRoute(): void
	{
		$host = HttpApplication::create()
			->register(PingHandler::class)
			->build();

		$this->expectException(HttpNotFoundException::class);
		$host->handle('GET', '/missing');
	}

	public function testHandleThrowsWhenMethodDoesNotMatch(): void
	{
		$host = HttpApplication::create()
			->register(CreateUserHandler::class)
			->build();

		$this->expectException(HttpNotFoundException::class);
		$host->handle('GET', '/users');
	}

	public function testMultipleHandlersResolveCorrectly(): void
	{
		$host = HttpApplication::create()
			->register(PingHandler::class)
			->register(GetUserHandler::class)
			->register(CreateUserHandler::class)
			->build();

		$this->assertSame('pong', $host->handle('GET', '/ping'));
		$this->assertSame('99', $host->handle('GET', '/users/99'));
		$this->assertSame('created', $host->handle('POST', '/users'));
	}

	public function testHandlerClassWithoutAttributeThrowsOnBuild(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		HttpApplication::create()
			->register(\stdClass::class)
			->build();
	}
}
