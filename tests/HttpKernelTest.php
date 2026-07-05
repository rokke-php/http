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
use Rokke\Http\Tests\Discovery\Fixture\PrefixInterceptor;
use Rokke\Http\Tests\Discovery\Fixture\TaggingMiddleware;
use Rokke\Runtime\Build\InvokerInterceptorCapability;
use Rokke\Runtime\Build\MiddlewareCapability;
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

		$body = json_encode(['name' => 'Fernando', 'email' => 'f@rokke.dev']);
		$this->assertIsString($body);
		$this->assertSame('created:Fernando', $kernel->host()->handle('POST', '/users', $body));
	}

	public function testBodyIsDeserializedToCommandDto(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$body = json_encode(['name' => 'Ana', 'email' => 'ana@rokke.dev']);
		$this->assertIsString($body);
		$this->assertSame('created:Ana', $kernel->host()->handle('POST', '/users', $body));
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

	public function testRouteParameterIsPassedToHandler(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('user:42', $kernel->host()->handle('GET', '/users/42'));
	}

	public function testRouteParameterIsCastToInt(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('user:7', $kernel->host()->handle('GET', '/users/7'));
	}

	public function testHeaderIsPassedToHandler(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('header:hello', $kernel->host()->handle('GET', '/header', headers: ['x-value' => 'hello']));
	}

	public function testNullableHeaderIsNullWhenAbsent(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('hello:world', $kernel->host()->handle('GET', '/optional-header'));
	}

	public function testNullableHeaderResolvedWhenPresent(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('hello:Fernando', $kernel->host()->handle('GET', '/optional-header', headers: ['x-name' => 'Fernando']));
	}

	public function testRequiredHeaderAbsentThrowsAtRuntime(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->expectException(\RuntimeException::class);
		$kernel->host()->handle('GET', '/header');
	}

	public function testQueryStringParamIsResolved(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('search:rokke', $kernel->host()->handle('GET', '/search', query: ['term' => 'rokke']));
	}

	public function testQueryStringParamIsCastToInt(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->assertSame('page:2,limit:15', $kernel->host()->handle('GET', '/paginate', query: ['page' => '2', 'per_page' => '15']));
	}

	public function testRequiredQueryParamAbsentThrowsAtRuntime(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$this->expectException(\RuntimeException::class);
		$kernel->host()->handle('GET', '/search');
	}

	public function testDtoReturnTypeIsSerializedToJson(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$result = $kernel->host()->handle('GET', '/profile/5');

		$this->assertIsString($result);
		$decoded = json_decode($result, true);
		$this->assertIsArray($decoded);
		$this->assertSame(5, $decoded['id']);
		$this->assertSame('Fernando', $decoded['name']);
	}

	public function testStringReturnTypePassesThroughUnchanged(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->build();

		$result = $kernel->host()->handle('GET', '/users/3');

		$this->assertSame('user:3', $result);
	}

	public function testRegisteredMiddlewareIsInvokedForEveryRequest(): void
	{
		TaggingMiddleware::$invoked = false;

		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->register(new class () implements ModuleInterface {
			public function register(ModuleBuilderInterface $builder): void
			{
				$builder->addCapability(new MiddlewareCapability(TaggingMiddleware::class));
			}
		});
		$kernel->build();

		$kernel->host()->handle('GET', '/ping');

		$this->assertTrue(TaggingMiddleware::$invoked);
	}

	public function testMiddlewareCanWrapHandlerResult(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->register(new class () implements ModuleInterface {
			public function register(ModuleBuilderInterface $builder): void
			{
				$builder->addCapability(new MiddlewareCapability(TaggingMiddleware::class));
			}
		});
		$kernel->build();

		$result = $kernel->host()->handle('GET', '/ping');

		$this->assertSame('[mw]pong', $result);
	}

	public function testRegisteredInterceptorIsInvokedForEveryRequest(): void
	{
		PrefixInterceptor::$invoked = false;

		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->register(new class () implements ModuleInterface {
			public function register(ModuleBuilderInterface $builder): void
			{
				$builder->addCapability(new InvokerInterceptorCapability(PrefixInterceptor::class));
			}
		});
		$kernel->build();

		$kernel->host()->handle('GET', '/ping');

		$this->assertTrue(PrefixInterceptor::$invoked);
	}

	public function testInterceptorRunsInsideMiddlewarePipeline(): void
	{
		$kernel = new HttpKernel();
		$kernel->register(new HttpModule(self::FIXTURE_DIR, self::FIXTURE_NS));
		$kernel->register(new class () implements ModuleInterface {
			public function register(ModuleBuilderInterface $builder): void
			{
				$builder->addCapability(new MiddlewareCapability(TaggingMiddleware::class));
				$builder->addCapability(new InvokerInterceptorCapability(PrefixInterceptor::class));
			}
		});
		$kernel->build();

		$result = $kernel->host()->handle('GET', '/ping');

		$this->assertSame('[mw][ic]pong', $result);
	}
}
