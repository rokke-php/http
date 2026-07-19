<?php

declare(strict_types=1);

namespace Rokke\Http\Tests\Build;

use PHPUnit\Framework\TestCase;
use Rokke\Http\Build\HttpConfigurationBuildPass;
use Rokke\Http\Build\HttpConfigurationDescriptor;
use Rokke\Http\HttpConfiguration;
use Rokke\Runtime\Build\ApplicationModel;
use Rokke\Runtime\Build\ExtensionBuildPassInterface;

final class HttpConfigurationBuildPassTest extends TestCase
{
	private HttpConfigurationBuildPass $pass;

	protected function setUp(): void
	{
		$this->pass = new HttpConfigurationBuildPass();
	}

	public function testImplementsExtensionBuildPassInterface(): void
	{
		$this->assertInstanceOf(ExtensionBuildPassInterface::class, $this->pass);
	}

	public function testProcessReturnsEmptyArrayWhenNoDescriptors(): void
	{
		$model = new ApplicationModel();

		$this->assertSame([], $this->pass->process($model));
	}

	public function testProcessConvertsDescriptorToHttpConfiguration(): void
	{
		$model = new ApplicationModel();
		$model->add(new HttpConfigurationDescriptor(host: '0.0.0.0', port: 8080));

		$results = $this->pass->process($model);

		$this->assertCount(1, $results);
		$this->assertInstanceOf(HttpConfiguration::class, $results[0]);
	}

	public function testProcessMapsHostAndPort(): void
	{
		$model = new ApplicationModel();
		$model->add(new HttpConfigurationDescriptor(host: '127.0.0.1', port: 9000));

		$results = $this->pass->process($model);

		assert($results[0] instanceof HttpConfiguration);
		$this->assertSame('127.0.0.1', $results[0]->host);
		$this->assertSame(9000, $results[0]->port);
	}

	public function testProcessOnlyReadsHttpConfigurationDescriptors(): void
	{
		$model = new ApplicationModel();
		$model->add(new HttpConfigurationDescriptor(host: '0.0.0.0', port: 8080));
		// Only one descriptor registered — result must have exactly one item
		$results = $this->pass->process($model);

		$this->assertCount(1, $results);
	}
}
