<?php

declare(strict_types=1);

namespace Rokke\Http\Tests;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Module\ModuleBuilderInterface;
use Rokke\Contracts\Module\ModuleInterface;
use Rokke\Http\HttpHost;
use Rokke\Http\HttpKernel;
use Rokke\Http\HttpModule;
use Rokke\Http\HttpNotFoundException;
use Rokke\Runtime\Build\OperationCapability;

final class HttpKernelTest extends TestCase
{
	private const FIXTURE_DIR = __DIR__ . '/Discovery/Fixture';
	private const FIXTURE_NS  = 'Rokke\Http\Tests\Discovery\Fixture';

	public function testRegisterReturnsSelf(): void
	{
		$kernel = new HttpKernel();
		$module = new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS);

		$this->assertSame($kernel, $kernel->register($module));
	}

	public function testBuildReturnsSelf(): void
	{
		$kernel = new HttpKernel();

		$this->assertSame($kernel, $kernel->build());
	}

	public function testHostThrowsBeforeBuild(): void
	{
		$this->expectException(\RuntimeException::class);

		$kernel = new HttpKernel();
		$kernel->host();
	}

	public function testHostReturnsHttpHostAfterBuild(): void
	{
		$kernel = new HttpKernel();
		$kernel->build();

		$this->assertInstanceOf(HttpHost::class, $kernel->host());
	}

	public function testHandleGetsRouteFromDiscoveredHandler(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('pong', $kernel->host()->handle('GET', '/ping'));
	}

	public function testHandlePostRouteFromDiscoveredHandler(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('created', $kernel->host()->handle('POST', '/users'));
	}

	public function testHandleThrowsForUnregisteredRoute(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->expectException(HttpNotFoundException::class);
		$kernel->host()->handle('GET', '/missing');
	}

	public function testExplicitCapabilitiesFromOtherModulesAreCompiled(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->register(new class () implements ModuleInterface {
			public function register(ModuleBuilderInterface $builder): void
			{
				$builder->addCapability(new OperationCapability(
					'extra',
					'Extra',
					static fn (): string => 'extra-result',
				));
			}
		});
		$kernel->build();

		$this->assertSame('pong', $kernel->host()->handle('GET', '/ping'));
	}

	public function testMultipleHttpModulesRoutesCoexist(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->register(new HttpModule(__DIR__ . '/Discovery/HealthFixture', 'Rokke\Http\Tests\Discovery\HealthFixture'));
		$kernel->build();

		$this->assertSame('pong', $kernel->host()->handle('GET', '/ping'));
		$this->assertSame('ok', $kernel->host()->handle('GET', '/health'));
	}

	public function testBuildWithNoModulesProducesEmptyRouteTree(): void
	{
		$kernel = new HttpKernel();
		$kernel->build();

		$this->expectException(HttpNotFoundException::class);
		$kernel->host()->handle('GET', '/anything');
	}
}
