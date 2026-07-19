<?php

declare(strict_types=1);

namespace Rokke\Http\Build;

use Rokke\Contracts\Configuration\ConfigurationDescriptorInterface;

final readonly class HttpConfigurationDescriptor implements ConfigurationDescriptorInterface
{
    public function __construct(
        public string $host,
        public int    $port,
    ) {}
}
