<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Module\ModuleInterface;
use Rokke\Http\Discovery\HttpDirectoryDiscoveryProvider;
use Rokke\Http\HttpModule;
use Rokke\Runtime\Module\ModuleBuilder;

final class HttpModuleTest extends TestCase
{
	private const FIXTURE_DIR = __DIR__ . '/Discovery/Fixture';
	private const FIXTURE_NS  = 'Rokke\Http\Tests\Discovery\Fixture';

	public function testRegisterAddsDiscoveryProviderToBuilder(): void
	{
		$builder = new ModuleBuilder();
		$module  = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);

		$module->register($builder);

		$this->assertCount(1, $builder->getDiscoveryProviders());
	}

	public function testRegisteredProviderIsHttpDirectoryDiscoveryProvider(): void
	{
		$builder = new ModuleBuilder();
		$module  = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);

		$module->register($builder);

		$this->assertInstanceOf(
			HttpDirectoryDiscoveryProvider::class,
			$builder->getDiscoveryProviders()[0],
		);
	}

	public function testRegisterDoesNotAddExplicitCapabilities(): void
	{
		$builder = new ModuleBuilder();
		$module  = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);

		$module->register($builder);

		$this->assertSame([], $builder->getCapabilities());
	}

	public function testImplementsModuleInterface(): void
	{
		$module = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);

		$this->assertInstanceOf(ModuleInterface::class, $module);
	}

	public function testMultipleModulesAddProvidersIndependently(): void
	{
		$builder = new ModuleBuilder();

		$moduleA = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);
		$moduleA->register($builder);

		$moduleB = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);
		$moduleB->register($builder);

		$this->assertCount(2, $builder->getDiscoveryProviders());
	}
}
