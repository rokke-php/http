<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Discovery;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\HttpCapability;
use Rokke\Http\Discovery\HttpDirectoryDiscoveryProvider;
use Rokke\Runtime\Build\OperationCapability;

final class HttpDirectoryDiscoveryProviderTest extends TestCase
{
	private const FIXTURE_DIR = __DIR__ . '/Fixture';
	private const FIXTURE_NS  = 'Rokke\Http\Tests\Discovery\Fixture';

	public function testDirectoryWithNoAttributedFilesReturnsEmptyArray(): void
	{
		$tmpDir = sys_get_temp_dir() . '/rokke_http_discovery_test_' . uniqid('', true);
		mkdir($tmpDir);

		try {
			$provider = new HttpDirectoryDiscoveryProvider($tmpDir, 'Some\Ns');
			$this->assertSame([], $provider->discover());
		} finally {
			rmdir($tmpDir);
		}
	}

	public function testSingleHandlerEmitsTwoCapabilities(): void
	{
		$tmpDir    = sys_get_temp_dir() . '/rokke_http_discovery_single_' . uniqid('', true);
		mkdir($tmpDir);
		copy(self::FIXTURE_DIR . '/PingHandler.php', $tmpDir . '/PingHandler.php');

		try {
			$provider     = new HttpDirectoryDiscoveryProvider($tmpDir, self::FIXTURE_NS);
			$capabilities = $provider->discover();

			$this->assertCount(2, $capabilities);
		} finally {
			unlink($tmpDir . '/PingHandler.php');
			rmdir($tmpDir);
		}
	}

	public function testDiscoveredHttpCapabilityHasCorrectMethodAndPath(): void
	{
		$provider = new HttpDirectoryDiscoveryProvider(
			directory: self::FIXTURE_DIR,
			namespace: self::FIXTURE_NS,
		);

		$httpCapabilities = array_filter(
			$provider->discover(),
			static fn ($cap): bool => $cap instanceof HttpCapability,
		);

		$routes = array_map(
			static fn (HttpCapability $cap): string => $cap->method . ':' . $cap->path,
			array_values($httpCapabilities),
		);

		$this->assertContains('GET:/ping', $routes);
		$this->assertContains('POST:/users', $routes);
	}

	public function testDiscoveredOperationCapabilityHasCorrectId(): void
	{
		$provider = new HttpDirectoryDiscoveryProvider(
			directory: self::FIXTURE_DIR,
			namespace: self::FIXTURE_NS,
		);

		$opCapabilities = array_filter(
			$provider->discover(),
			static fn ($cap): bool => $cap instanceof OperationCapability,
		);

		$ids = array_map(
			static fn (OperationCapability $cap): string => $cap->id,
			array_values($opCapabilities),
		);

		$this->assertContains('PingHandler', $ids);
		$this->assertContains('CreateUserHandler', $ids);
	}

	public function testFileWithoutAttributeIsSkipped(): void
	{
		$provider = new HttpDirectoryDiscoveryProvider(
			directory: self::FIXTURE_DIR,
			namespace: self::FIXTURE_NS,
		);

		$opCapabilities = array_filter(
			$provider->discover(),
			static fn ($cap): bool => $cap instanceof OperationCapability,
		);

		$ids = array_map(
			static fn (OperationCapability $cap): string => $cap->id,
			array_values($opCapabilities),
		);

		$this->assertNotContains('PlainService', $ids);
	}

	public function testEachHandlerEmitsHttpAndOperationCapabilityWithMatchingId(): void
	{
		$provider     = new HttpDirectoryDiscoveryProvider(
			directory: self::FIXTURE_DIR,
			namespace: self::FIXTURE_NS,
		);
		$capabilities = $provider->discover();

		$httpIds = array_map(
			static fn (HttpCapability $cap): string => $cap->operationId,
			array_values(array_filter($capabilities, static fn ($c): bool => $c instanceof HttpCapability)),
		);

		$opIds = array_map(
			static fn (OperationCapability $cap): string => $cap->id,
			array_values(array_filter($capabilities, static fn ($c): bool => $c instanceof OperationCapability)),
		);

		sort($httpIds);
		sort($opIds);
		$this->assertSame($httpIds, $opIds);
	}
}
