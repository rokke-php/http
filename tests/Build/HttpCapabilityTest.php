<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Module\CapabilityInterface;
use Rokke\Http\Build\HttpCapability;

final class HttpCapabilityTest extends TestCase
{
	public function testImplementsCapabilityInterface(): void
	{
		$cap = new HttpCapability('GET', '/users', 'users.list');

		$this->assertInstanceOf(CapabilityInterface::class, $cap);
	}

	public function testHoldsMethod(): void
	{
		$cap = new HttpCapability('POST', '/users', 'users.create');

		$this->assertSame('POST', $cap->method);
	}

	public function testHoldsPath(): void
	{
		$cap = new HttpCapability('GET', '/users/{id}', 'users.show');

		$this->assertSame('/users/{id}', $cap->path);
	}

	public function testHoldsOperationId(): void
	{
		$cap = new HttpCapability('DELETE', '/users/{id}', 'users.delete');

		$this->assertSame('users.delete', $cap->operationId);
	}

	public function testAcceptsMixedCaseMethod(): void
	{
		$cap = new HttpCapability('get', '/ping', 'ping');

		$this->assertSame('get', $cap->method);
	}
}
