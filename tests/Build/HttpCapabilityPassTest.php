<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Contracts\Module\CapabilityInterface;
use Rokke\Http\Build\HttpCapability;
use Rokke\Http\Build\HttpCapabilityPass;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Runtime\Build\ApplicationModel;
use Rokke\Runtime\Build\ModelBuilderPassInterface;

// ── Fixture — an unrelated capability ─────────────────────────────────────────

final class OtherCapability implements CapabilityInterface {}

// ── Tests ─────────────────────────────────────────────────────────────────────

final class HttpCapabilityPassTest extends TestCase
{
	private HttpCapabilityPass $pass;
	private ApplicationModel $model;

	protected function setUp(): void
	{
		$this->pass  = new HttpCapabilityPass();
		$this->model = new ApplicationModel();
	}

	public function testImplementsModelBuilderPassInterface(): void
	{
		$this->assertInstanceOf(ModelBuilderPassInterface::class, $this->pass);
	}

	public function testEmptyCapabilitiesProducesNoRouteDescriptors(): void
	{
		$this->pass->process([], $this->model);

		$this->assertSame([], $this->model->definitions(RouteDescriptor::class));
	}

	public function testHttpCapabilityProducesRouteDescriptor(): void
	{
		$this->pass->process(
			[new HttpCapability('GET', '/users', 'users.list')],
			$this->model,
		);

		$descriptors = $this->model->definitions(RouteDescriptor::class);
		$this->assertCount(1, $descriptors);
		$this->assertSame('/users', $descriptors[0]->path);
		$this->assertSame('users.list', $descriptors[0]->operationId);
	}

	public function testMethodIsNormalizedToUppercase(): void
	{
		$this->pass->process(
			[new HttpCapability('get', '/ping', 'ping')],
			$this->model,
		);

		$descriptors = $this->model->definitions(RouteDescriptor::class);
		$this->assertSame('GET', $descriptors[0]->method);
	}

	public function testNonHttpCapabilitiesAreIgnored(): void
	{
		$this->pass->process([new OtherCapability()], $this->model);

		$this->assertSame([], $this->model->definitions(RouteDescriptor::class));
	}

	public function testMixedCapabilitiesOnlyProcessesHttpOnes(): void
	{
		$this->pass->process([
			new OtherCapability(),
			new HttpCapability('POST', '/users', 'users.create'),
			new OtherCapability(),
			new HttpCapability('DELETE', '/users/{id}', 'users.delete'),
		], $this->model);

		$descriptors = $this->model->definitions(RouteDescriptor::class);
		$this->assertCount(2, $descriptors);
		$this->assertSame('POST', $descriptors[0]->method);
		$this->assertSame('DELETE', $descriptors[1]->method);
	}

	public function testMultipleRoutesPreserveOrder(): void
	{
		$this->pass->process([
			new HttpCapability('GET', '/a', 'a'),
			new HttpCapability('GET', '/b', 'b'),
			new HttpCapability('GET', '/c', 'c'),
		], $this->model);

		$descriptors = $this->model->definitions(RouteDescriptor::class);
		$this->assertSame('/a', $descriptors[0]->path);
		$this->assertSame('/b', $descriptors[1]->path);
		$this->assertSame('/c', $descriptors[2]->path);
	}
}
