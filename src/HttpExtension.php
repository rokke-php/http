<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Contracts\Extension\ExtensionBuildInterface;
use Rokke\Contracts\Extension\ExtensionBuilderInterface;
use Rokke\Contracts\Extension\ExtensionInterface;
use Rokke\Http\Build\HttpConfigurationBuildPass;
use Rokke\Http\Build\HttpConfigurationDescriptor;
use Rokke\Http\Discovery\HttpDirectoryDiscoveryProvider;

/**
 * Registers HTTP route discovery and compiled configuration for a directory
 * of annotated handler classes.
 *
 * Pass host and port here so they are compiled into the runtime artifact —
 * no dynamic parameters at dispatch time.
 */
final class HttpExtension implements ExtensionInterface, ExtensionBuildInterface
{
	public function __construct(
		private readonly string $directory,
		private readonly string $namespace,
		private readonly string $host = '0.0.0.0',
		private readonly int    $port = 8080,
	) {}

	public function register(ExtensionBuilderInterface $builder): void
	{
		$builder->addDiscoveryProvider(
			new HttpDirectoryDiscoveryProvider($this->directory, $this->namespace),
		);

		$builder->configuration(
			new HttpConfigurationDescriptor(host: $this->host, port: $this->port),
		);
	}

	public function buildPasses(): iterable
	{
		return [new HttpConfigurationBuildPass()];
	}
}
