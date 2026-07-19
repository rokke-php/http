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
        $descriptors = $model->definitions(HttpConfigurationDescriptor::class);

        if ($descriptors === []) {
            return [];
        }

        // Only one HTTP server can exist per runtime; take the first descriptor.
        $first = $descriptors[0];

        return [new HttpConfiguration(host: $first->host, port: $first->port)];
    }
}
