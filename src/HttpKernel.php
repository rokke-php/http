<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Contracts\Extension\ExtensionInterface;
use Rokke\Http\Build\BodyArgumentSourceCompiler;
use Rokke\Http\Build\HeaderArgumentSourceCompiler;
use Rokke\Http\Build\HttpCapabilityPass;
use Rokke\Http\Build\JsonResultSourceCompiler;
use Rokke\Http\Build\QueryArgumentSourceCompiler;
use Rokke\Http\Build\RouteCompiler;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Build\RouteParameterArgumentSourceCompiler;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\Emitter\EmitterInterface;
use Rokke\Runtime\Build\ArgumentPlanCompiler;
use Rokke\Runtime\Build\DiscoveryEngine;
use Rokke\Runtime\Build\FactoryCompiler;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Build\HandlerCompiler;
use Rokke\Runtime\Build\MaxValidationSourceCompiler;
use Rokke\Runtime\Build\MinValidationSourceCompiler;
use Rokke\Runtime\Build\ModelBuilder;
use Rokke\Runtime\Build\NotBlankValidationSourceCompiler;
use Rokke\Runtime\Build\OperationDefinition;
use Rokke\Runtime\Build\OperationModelBuilderPass;
use Rokke\Runtime\Build\ResultPlanCompiler;
use Rokke\Runtime\Build\ServiceDescriptor;
use Rokke\Runtime\Build\ServiceModelBuilderPass;
use Rokke\Runtime\Build\ValidationPlanCompiler;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledBehaviorPipeline;
use Rokke\Runtime\Compiled\CompiledExecutionPipeline;
use Rokke\Runtime\Compiled\CompiledInterceptorPipeline;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Extension\ExtensionBuilder;
use Rokke\Runtime\Extension\ExtensionRegistry;

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
	private ExtensionRegistry $extensions;
	private ?HttpHost $host = null;

	public function __construct()
	{
		$this->extensions = new ExtensionRegistry();
	}

	public function register(ExtensionInterface $extension): self
	{
		$this->extensions->register($extension);

		return $this;
	}

	public function build(?EmitterInterface $emitter = null): self
	{
		$extensionBuilder = new ExtensionBuilder();
		$this->extensions->buildAll($extensionBuilder);

		$engine          = new DiscoveryEngine();
		$discovered      = $engine->run($extensionBuilder->getDiscoveryProviders());
		$allCapabilities = [...$extensionBuilder->getCapabilities(), ...$discovered];

		$modelBuilder = new ModelBuilder([
			new HttpCapabilityPass(),
			new OperationModelBuilderPass(),
			new ServiceModelBuilderPass(),
		]);
		$model = $modelBuilder->build($allCapabilities);

		$routeCompiler = new RouteCompiler();
		$routeTree     = $routeCompiler->compile($model->definitions(RouteDescriptor::class));

		$factories      = FactoryRepository::build($model->definitions(ServiceDescriptor::class), new FactoryCompiler());
		$handlerCompiler   = new HandlerCompiler();
		$argCompiler    = new ArgumentPlanCompiler([
			new HeaderArgumentSourceCompiler(),
			new QueryArgumentSourceCompiler(),
			new RouteParameterArgumentSourceCompiler(),
			new BodyArgumentSourceCompiler(),
		]);
		$resultCompiler     = new ResultPlanCompiler([new JsonResultSourceCompiler()]);
		$validationCompiler = new ValidationPlanCompiler([
			new NotBlankValidationSourceCompiler(),
			new MinValidationSourceCompiler(),
			new MaxValidationSourceCompiler(),
		]);
		$handlers          = [];
		$argumentPlans     = [];
		$resultPlans       = [];
		$validationPlans   = [];
		$behaviorPipelines = [];
		$compiledOps       = [];

		foreach ($model->definitions(OperationDefinition::class) as $index => $definition) {
			$handlers[$index]        = $handlerCompiler->compile($definition->handler, $factories);
			$argumentPlans[$index]   = $argCompiler->compile($definition->handler, $factories);
			$resultPlans[$index]     = $resultCompiler->compile($definition->handler);
			$validationPlans[$index] = $validationCompiler->compile($definition->handler);

			$behaviorPipelineId = null;

			if ($definition->behaviors !== []) {
				$behaviorPipelineId                     = $index;
				$behaviorPipelines[$behaviorPipelineId] = new CompiledBehaviorPipeline(
					array_map(static fn ($d) => $d->behavior, $definition->behaviors),
				);
			}

			$compiledOps[] = new CompiledOperation(
				id: $definition->id,
				pipelineId: 0,
				handlerId: $index,
				argumentPlanId: $index,
				resultPlanId: $index,
				behaviorPipelineId: $behaviorPipelineId,
				validationPlanId: $index,
			);
		}

		$executionPipeline = new CompiledExecutionPipeline(
			handlers: $handlers,
			argumentPlans: $argumentPlans,
			resultPlans: $resultPlans,
			behaviorPipelines: $behaviorPipelines,
			validationPlans: $validationPlans,
		);

		$runtime = new CompiledRuntime(
			executionPipeline: $executionPipeline,
			interceptorPipeline: CompiledInterceptorPipeline::empty(),
			operations: OperationRepository::build($compiledOps),
			factories: $factories,
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
