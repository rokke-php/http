<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Contracts\Module\ModuleInterface;
use Rokke\Http\Build\BodyArgumentSourceCompiler;
use Rokke\Http\Build\HttpCapabilityPass;
use Rokke\Http\Build\RouteCompiler;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Build\RouteParameterArgumentSourceCompiler;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\Emitter\EmitterInterface;
use Rokke\Runtime\Build\ArgumentPlanCompiler;
use Rokke\Runtime\Build\DiscoveryEngine;
use Rokke\Runtime\Build\FactoryCompiler;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Build\ModelBuilder;
use Rokke\Runtime\Build\OperationDefinition;
use Rokke\Runtime\Build\OperationModelBuilderPass;
use Rokke\Runtime\Build\ResultPlanCompiler;
use Rokke\Runtime\Build\ServiceDescriptor;
use Rokke\Runtime\Build\ServiceModelBuilderPass;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Module\ModuleBuilder;
use Rokke\Runtime\Module\ModuleSystem;

/**
 * Composition root for HTTP applications built from modules.
 *
 * Wires the HTTP build pipeline (HttpCapabilityPass, RouteCompiler) together
 * with the standard runtime pipeline (OperationModelBuilderPass, DiscoveryEngine)
 * and produces an HttpHost ready to serve requests.
 *
 * Usage:
 *   (new HttpKernel())
 *       ->register(new HttpModule(__DIR__ . '/app/Handler', 'App\Handler'))
 *       ->build()
 *       ->run('0.0.0.0', 8080);
 */
final class HttpKernel
{
	private ModuleSystem $modules;
	private ?HttpHost $host = null;

	public function __construct()
	{
		$this->modules = new ModuleSystem();
	}

	public function register(ModuleInterface $module): self
	{
		$this->modules->register($module);

		return $this;
	}

	public function build(?EmitterInterface $emitter = null): self
	{
		$moduleBuilder = new ModuleBuilder();
		$this->modules->buildAll($moduleBuilder);

		$engine          = new DiscoveryEngine();
		$discovered      = $engine->run($moduleBuilder->getDiscoveryProviders());
		$allCapabilities = [...$moduleBuilder->getCapabilities(), ...$discovered];

		$modelBuilder = new ModelBuilder([
			new HttpCapabilityPass(),
			new OperationModelBuilderPass(),
			new ServiceModelBuilderPass(),
		]);
		$model = $modelBuilder->build($allCapabilities);

		$routeCompiler = new RouteCompiler();
		$routeTree     = $routeCompiler->compile($model->definitions(RouteDescriptor::class));

		$factories      = FactoryRepository::build($model->definitions(ServiceDescriptor::class), new FactoryCompiler());
		$argCompiler    = new ArgumentPlanCompiler([
			new RouteParameterArgumentSourceCompiler(),
			new BodyArgumentSourceCompiler(),
		]);
		$resultCompiler = new ResultPlanCompiler();
		$handlers       = [];
		$argumentPlans  = [];
		$resultPlans    = [];
		$compiledOps    = [];

		foreach ($model->definitions(OperationDefinition::class) as $index => $definition) {
			$handlers[$index]      = $definition->handler;
			$argumentPlans[$index] = $argCompiler->compile($definition->handler, $factories);
			$resultPlans[$index]   = $resultCompiler->compile($definition->handler);
			$compiledOps[]         = new CompiledOperation($definition->id, 0, $index, $index, $index);
		}

		$runtime = new CompiledRuntime(
			pipelines: [],
			handlers: $handlers,
			argumentPlans: $argumentPlans,
			resultPlans: $resultPlans,
			operations: OperationRepository::build($compiledOps),
			artifacts: ArtifactRepository::build([CompiledRouteTree::class => $routeTree]),
		);

		$this->host = new HttpHost($runtime, $emitter);

		return $this;
	}

	public function host(): HttpHost
	{
		if ($this->host === null) {
			throw new \RuntimeException('Call build() before host().');
		}

		return $this->host;
	}

	public function run(string $host, int $port): void
	{
		$this->host()->run($host, $port);
	}
}
