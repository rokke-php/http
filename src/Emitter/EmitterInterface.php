<?php

declare(strict_types=1);

namespace Rokke\Http\Emitter;

interface EmitterInterface
{
	public function emit(mixed $result, \Swoole\Http\Response $response): void;
}
