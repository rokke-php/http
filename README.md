# rokke/http

[![CI](https://github.com/rokke-php/http/actions/workflows/ci.yml/badge.svg)](https://github.com/rokke-php/http/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/github/v/tag/rokke-php/http?label=version)](https://github.com/rokke-php/http/releases)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-8892be)](https://www.php.net)
[![License](https://img.shields.io/github/license/rokke-php/http)](LICENSE)

HTTP transport adapter for the [Rokke Runtime](https://github.com/rokke-php/runtime).

## What this is

`rokke/http` is not an HTTP framework — it is an HTTP adapter. It takes Swoole HTTP requests, resolves them against a compiled route tree, and dispatches them to the Rokke Runtime's `ExecutionEngine`. The Runtime never knows a URL existed. This package owns the entire HTTP boundary: route compilation, request parsing, context construction, and response emission.

## Installation

```bash
composer require rokke/http
```

Requires PHP ≥ 8.4, `ext-swoole ≥ 5.0`, `rokke/runtime ^0.7`, and `rokke/contracts ^0.4`.

## Architecture

```
Swoole HTTP Request
        ↓
    HttpHost
        ↓
 CompiledRouteTree        ← compiled at Build time, never rebuilt
        ↓
  OperationContext        ← path params + headers + body parsed here
        ↓
  ExecutionEngine         ← unchanged; knows nothing about HTTP
        ↓
  ResultEmitter           ← converts Operation result → Swoole Response
        ↓
Swoole HTTP Response
```

The `CompiledRouteTree` is registered in the Runtime's `ArtifactRepository` during the Build phase. At runtime, `HttpHost` retrieves it via `$runtime->artifacts->get(CompiledRouteTree::class)` — no direct coupling between the HTTP module and the Runtime internals.

## When to use

Use `rokke/http` when you need an HTTP transport for a Rokke application. For CLI, queue, or gRPC transports, use the corresponding adapter packages instead.

## Stability

Pre-1.0. API may change between minor versions. Pin to an exact minor version in production.

## License

MIT
