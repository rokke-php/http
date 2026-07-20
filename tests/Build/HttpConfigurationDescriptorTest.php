<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Build\DefinitionInterface;
use Rokke\Contracts\Configuration\ConfigurationDescriptorInterface;
use Rokke\Http\Build\HttpConfigurationDescriptor;

final class HttpConfigurationDescriptorTest extends TestCase
{
	public function testImplementsConfigurationDescriptorInterface(): void
	{
		$descriptor = new HttpConfigurationDescriptor(host: '0.0.0.0', port: 8080);

		$this->assertInstanceOf(ConfigurationDescriptorInterface::class, $descriptor);
		$this->assertInstanceOf(DefinitionInterface::class, $descriptor);
	}

	public function testExposesHost(): void
	{
		$descriptor = new HttpConfigurationDescriptor(host: '127.0.0.1', port: 9000);

		$this->assertSame('127.0.0.1', $descriptor->host);
	}

	public function testExposesPort(): void
	{
		$descriptor = new HttpConfigurationDescriptor(host: '0.0.0.0', port: 3000);

		$this->assertSame(3000, $descriptor->port);
	}
}
