<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Build\DefinitionInterface;
use Rokke\Http\Build\RouteDescriptor;

final class RouteDescriptorTest extends TestCase
{
	public function testImplementsDefinitionInterface(): void
	{
		$desc = new RouteDescriptor('GET', '/users', 'users.list');

		$this->assertInstanceOf(DefinitionInterface::class, $desc);
	}

	public function testHoldsMethod(): void
	{
		$desc = new RouteDescriptor('POST', '/users', 'users.create');

		$this->assertSame('POST', $desc->method);
	}

	public function testHoldsPath(): void
	{
		$desc = new RouteDescriptor('GET', '/users/{id}', 'users.show');

		$this->assertSame('/users/{id}', $desc->path);
	}

	public function testHoldsOperationId(): void
	{
		$desc = new RouteDescriptor('DELETE', '/users/{id}', 'users.delete');

		$this->assertSame('users.delete', $desc->operationId);
	}
}
