<?php

declare(strict_types=1);

namespace Rokke\Http;

final readonly class HttpConfiguration
{
    public function __construct(
        public string $host,
        public int    $port,
    ) {}
}
