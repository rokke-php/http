<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Attribute\Delete;
use Rokke\Http\Attribute\Get;
use Rokke\Http\Attribute\HttpMethodAttribute;
use Rokke\Http\Attribute\Patch;
use Rokke\Http\Attribute\Post;
use Rokke\Http\Attribute\Put;

final class HttpMethodAttributeTest extends TestCase
{
	public function testGetMethodReturnsGet(): void
	{
		$attr = new Get('/path');
		$this->assertSame('GET', $attr->method());
	}

	public function testPostMethodReturnsPost(): void
	{
		$attr = new Post('/path');
		$this->assertSame('POST', $attr->method());
	}

	public function testPutMethodReturnsPut(): void
	{
		$attr = new Put('/path');
		$this->assertSame('PUT', $attr->method());
	}

	public function testPatchMethodReturnsPatch(): void
	{
		$attr = new Patch('/path');
		$this->assertSame('PATCH', $attr->method());
	}

	public function testDeleteMethodReturnsDelete(): void
	{
		$attr = new Delete('/path');
		$this->assertSame('DELETE', $attr->method());
	}

	public function testPathIsPreservedOnGet(): void
	{
		$attr = new Get('/users/{id}');
		$this->assertSame('/users/{id}', $attr->path);
	}

	public function testPathIsPreservedOnPost(): void
	{
		$attr = new Post('/users');
		$this->assertSame('/users', $attr->path);
	}

	public function testPathIsPreservedOnPut(): void
	{
		$attr = new Put('/users/{id}');
		$this->assertSame('/users/{id}', $attr->path);
	}

	public function testPathIsPreservedOnPatch(): void
	{
		$attr = new Patch('/users/{id}');
		$this->assertSame('/users/{id}', $attr->path);
	}

	public function testPathIsPreservedOnDelete(): void
	{
		$attr = new Delete('/users/{id}');
		$this->assertSame('/users/{id}', $attr->path);
	}

	public function testGetExtendsHttpMethodAttribute(): void
	{
		$attr = new Get('/');
		$this->assertInstanceOf(HttpMethodAttribute::class, $attr);
	}

	public function testPostExtendsHttpMethodAttribute(): void
	{
		$attr = new Post('/');
		$this->assertInstanceOf(HttpMethodAttribute::class, $attr);
	}

	public function testPutExtendsHttpMethodAttribute(): void
	{
		$attr = new Put('/');
		$this->assertInstanceOf(HttpMethodAttribute::class, $attr);
	}

	public function testPatchExtendsHttpMethodAttribute(): void
	{
		$attr = new Patch('/');
		$this->assertInstanceOf(HttpMethodAttribute::class, $attr);
	}

	public function testDeleteExtendsHttpMethodAttribute(): void
	{
		$attr = new Delete('/');
		$this->assertInstanceOf(HttpMethodAttribute::class, $attr);
	}

	public function testGetIsRegisteredAsPhpAttribute(): void
	{
		$reflection = new \ReflectionClass(Get::class);
		$attrs      = $reflection->getAttributes(\Attribute::class);
		$this->assertCount(1, $attrs);
	}
}
