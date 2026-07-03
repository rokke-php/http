<?php

// PHPStan stubs for Swoole classes.
// These stubs are used only during static analysis.
// At runtime, the real ext-swoole extension provides these implementations.

namespace Swoole;

final class Coroutine
{
    public static function getCid(): int {}
    /** @return \ArrayObject<string, mixed> */
    public static function getContext(int $cid = -1): \ArrayObject {}
    public static function create(callable $callable, mixed ...$args): int|false {}
    public static function yield(): bool {}
    public static function cancel(int $coroutineId): bool {}
}

namespace Swoole\Coroutine;

final class Channel
{
    public function __construct(int $capacity = 1) {}
    public function push(mixed $data, float $timeout = -1.0): bool {}
    public function pop(float $timeout = -1.0): mixed {}
    /** @return array{queue_num: int, consumer_num: int} */
    public function stats(): array {}
    public function close(): bool {}
}

// @phpstan-ignore-next-line
final class WaitGroup
{
    public function add(int $delta = 1): void {}
    public function done(): void {}
    public function wait(float $timeout = -1.0): bool {}
}

final class System
{
    public static function sleep(float $seconds): bool {}
}

namespace Swoole\Http;

final class Request
{
    /** @var array<string, string> */
    public array $server = [];

    /** @var array<string, string> */
    public array $header = [];

    /** @var array<string, mixed> */
    public array $get = [];

    /** @var array<string, mixed> */
    public array $post = [];

    public function rawContent(): ?string {}
}

final class Response
{
    public function status(int $code, string $reason = ''): bool {}

    public function header(string $key, string $value, bool $format = true): bool {}

    public function end(string $body = ''): bool {}
}

final class Server
{
    public function __construct(string $host, int $port, int $mode = 2, int $sockType = 1) {}

    public function on(string $event, callable $callback): bool {}

    public function start(): bool {}

    public function stop(): bool {}
}
