<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Http\Compiled\RouteMatch;
use Rokke\Runtime\Context\OperationContext;

final class HttpContextFactory
{
	/**
	 * @param array<string, string> $headers
	 * @param array<string, string> $query
	 */
	public function fromMatch(
		RouteMatch $match,
		array $headers = [],
		string $body = '',
		array $query = [],
	): OperationContext {
		return new OperationContext(
			id: uniqid('http-', true),
			metadata: [
				'params'  => $match->params,
				'headers' => $headers,
				'body'    => $body,
				'query'   => $query,
			],
		);
	}

	public function fromRequest(\Swoole\Http\Request $request, RouteMatch $match): OperationContext
	{
		return $this->fromMatch(
			match: $match,
			headers: $request->header ?? [],
			body: $request->rawContent() ?: '',
			query: $request->get ?? [],
		);
	}
}
