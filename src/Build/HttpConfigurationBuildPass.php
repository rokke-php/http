<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Http\HttpConfiguration;
use Rokke\Runtime\Build\ApplicationModel;
use Rokke\Runtime\Build\ExtensionBuildPassInterface;

final class HttpConfigurationBuildPass implements ExtensionBuildPassInterface
{
	public function process(ApplicationModel $model): array
	{
		return array_map(
			static fn (HttpConfigurationDescriptor $d) => new HttpConfiguration(
				host: $d->host,
				port: $d->port,
			),
			$model->definitions(HttpConfigurationDescriptor::class),
		);
	}
}
