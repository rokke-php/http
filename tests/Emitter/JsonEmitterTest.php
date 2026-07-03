<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Emitter;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Emitter\EmitterInterface;
use Rokke\Http\Emitter\JsonEmitter;

final class JsonEmitterTest extends TestCase
{
	private JsonEmitter $emitter;

	protected function setUp(): void
	{
		$this->emitter = new JsonEmitter();
	}

	public function testImplementsEmitterInterface(): void
	{
		$this->assertInstanceOf(EmitterInterface::class, $this->emitter);
	}

	public function testEncodesStringValue(): void
	{
		$this->assertSame('"hello"', $this->emitter->encode('hello'));
	}

	public function testEncodesIntegerValue(): void
	{
		$this->assertSame('42', $this->emitter->encode(42));
	}

	public function testEncodesFloatValue(): void
	{
		$this->assertSame('3.14', $this->emitter->encode(3.14));
	}

	public function testEncodesBooleanTrue(): void
	{
		$this->assertSame('true', $this->emitter->encode(true));
	}

	public function testEncodesNull(): void
	{
		$this->assertSame('null', $this->emitter->encode(null));
	}

	public function testEncodesAssociativeArray(): void
	{
		$this->assertSame('{"id":1,"name":"Alice"}', $this->emitter->encode(['id' => 1, 'name' => 'Alice']));
	}

	public function testEncodesListArray(): void
	{
		$this->assertSame('[1,2,3]', $this->emitter->encode([1, 2, 3]));
	}

	public function testEncodesNestedArray(): void
	{
		$this->assertSame('{"user":{"id":7}}', $this->emitter->encode(['user' => ['id' => 7]]));
	}

	public function testPreservesUnicodeCharacters(): void
	{
		$encoded = $this->emitter->encode('héllo');
		$this->assertSame('"héllo"', $encoded);
	}

	public function testPreservesForwardSlashes(): void
	{
		$encoded = $this->emitter->encode('api/v1/users');
		$this->assertSame('"api/v1/users"', $encoded);
	}
}
