<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Extension\ExtensionInterface;
use Rokke\Http\Discovery\HttpDirectoryDiscoveryProvider;
use Rokke\Http\HttpExtension;
use Rokke\Runtime\Extension\ExtensionBuilder;

final class HttpExtensionTest extends TestCase
{
	private const FIXTURE_DIR = __DIR__ . '/Discovery/Fixture';
	private const FIXTURE_NS  = 'Rokke\Http\Tests\Discovery\Fixture';

	public function testRegisterAddsDiscoveryProviderToBuilder(): void
	{
		$builder   = new ExtensionBuilder();
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$extension->register($builder);

		$this->assertCount(1, $builder->getDiscoveryProviders());
	}

	public function testRegisteredProviderIsHttpDirectoryDiscoveryProvider(): void
	{
		$builder   = new ExtensionBuilder();
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$extension->register($builder);

		$this->assertInstanceOf(
			HttpDirectoryDiscoveryProvider::class,
			$builder->getDiscoveryProviders()[0],
		);
	}

	public function testRegisterDoesNotAddExplicitCapabilities(): void
	{
		$builder   = new ExtensionBuilder();
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$extension->register($builder);

		$this->assertSame([], $builder->getCapabilities());
	}

	public function testImplementsExtensionInterface(): void
	{
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$this->assertInstanceOf(ExtensionInterface::class, $extension);
	}

	public function testMultipleExtensionsAddProvidersIndependently(): void
	{
		$builder = new ExtensionBuilder();

		$extA = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);
		$extA->register($builder);

		$extB = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);
		$extB->register($builder);

		$this->assertCount(2, $builder->getDiscoveryProviders());
	}
}
