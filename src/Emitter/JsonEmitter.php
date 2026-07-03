<?php

declare(strict_types=1);

namespace Rokke\Http\Emitter;

final class JsonEmitter implements EmitterInterface
{
	public function encode(mixed $result): string
	{
		return json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function emit(mixed $result, \Swoole\Http\Response $response): void
	{
		$body = $this->encode($result);
		$response->header('Content-Type', 'application/json; charset=utf-8');
		$response->end($body);
	}
}
