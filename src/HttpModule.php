<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Contracts\Module\ModuleBuilderInterface;
use Rokke\Contracts\Module\ModuleInterface;
use Rokke\Http\Discovery\HttpDirectoryDiscoveryProvider;

/**
 * Registers HTTP route discovery for a directory of annotated handler classes.
 *
 * Each class in the given directory bearing a HTTP method attribute
 * (#[Get], #[Post], #[Put], #[Patch], #[Delete]) is discovered at Build time
 * and emits the corresponding capabilities into the application graph.
 *
 * Register this module with HttpKernel to wire routes into the HTTP pipeline.
 */
final class HttpModule implements ModuleInterface
{
	public function __construct(
		private readonly string $directory,
		private readonly string $namespace,
	) {}

	public function register(ModuleBuilderInterface $builder): void
	{
		$builder->addDiscoveryProvider(
			new HttpDirectoryDiscoveryProvider($this->directory, $this->namespace),
		);
	}
}
