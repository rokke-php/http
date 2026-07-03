<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Contracts\Module\CapabilityInterface;
use Rokke\Runtime\Build\ApplicationModel;
use Rokke\Runtime\Build\ModelBuilderPassInterface;

final class HttpCapabilityPass implements ModelBuilderPassInterface
{
	/** @param list<CapabilityInterface> $capabilities */
	public function process(array $capabilities, ApplicationModel $model): void
	{
		foreach ($capabilities as $capability) {
			if (!$capability instanceof HttpCapability) {
				continue;
			}

			$model->add(new RouteDescriptor(
				method: strtoupper($capability->method),
				path: $capability->path,
				operationId: $capability->operationId,
			));
		}
	}
}
