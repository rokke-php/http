<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Extension\ExtensionBuildInterface;
use Rokke\Contracts\Extension\ExtensionInterface;
use Rokke\Http\Build\HttpConfigurationDescriptor;
use Rokke\Http\Discovery\HttpDirectoryDiscoveryProvider;
use Rokke\Http\HttpExtension;
use Rokke\Runtime\Build\ExtensionBuildPassInterface;
use Rokke\Runtime\Extension\ExtensionBuilder;

final class HttpExtensionTest extends TestCase
{
	private const FIXTURE_DIR = __DIR__ . '/Discovery/Fixture';
	private const FIXTURE_NS  = 'Rokke\Http\Tests\Discovery\Fixture';

	public function testImplementsExtensionInterface(): void
	{
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$this->assertInstanceOf(ExtensionInterface::class, $extension);
	}

	public function testImplementsExtensionBuildInterface(): void
	{
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$this->assertInstanceOf(ExtensionBuildInterface::class, $extension);
	}

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

	public function testRegisterAddsConfigurationDescriptor(): void
	{
		$builder   = new ExtensionBuilder();
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS, host: '0.0.0.0', port: 8080);

		$extension->register($builder);

		$this->assertCount(1, $builder->getConfigurationDescriptors());
		$this->assertInstanceOf(HttpConfigurationDescriptor::class, $builder->getConfigurationDescriptors()[0]);
	}

	public function testRegisterDescriptorCarriesHostAndPort(): void
	{
		$builder   = new ExtensionBuilder();
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS, host: '127.0.0.1', port: 9000);

		$extension->register($builder);

		$descriptor = $builder->getConfigurationDescriptors()[0];
		assert($descriptor instanceof HttpConfigurationDescriptor);
		$this->assertSame('127.0.0.1', $descriptor->host);
		$this->assertSame(9000, $descriptor->port);
	}

	public function testBuildPassesReturnsHttpConfigurationBuildPass(): void
	{
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$passes = [...$extension->buildPasses()];

		$this->assertCount(1, $passes);
		$this->assertInstanceOf(ExtensionBuildPassInterface::class, $passes[0]);
	}

	public function testRegisterDoesNotAddExplicitCapabilities(): void
	{
		$builder   = new ExtensionBuilder();
		$extension = new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS);

		$extension->register($builder);

		$this->assertSame([], $builder->getCapabilities());
	}

	public function testMultipleExtensionsAddProvidersIndependently(): void
	{
		$builder = new ExtensionBuilder();

		(new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS))->register($builder);
		(new HttpExtension(self::FIXTURE_DIR, self::FIXTURE_NS))->register($builder);

		$this->assertCount(2, $builder->getDiscoveryProviders());
	}
}
