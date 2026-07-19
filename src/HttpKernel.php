<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Contracts\Extension\ExtensionInterface;
use Rokke\Http\Build\BodyArgumentSourceCompiler;
use Rokke\Http\Build\HeaderArgumentSourceCompiler;
use Rokke\Http\Build\HttpCapabilityPass;
use Rokke\Http\Build\JsonResultSourceCompiler;
use Rokke\Http\Build\QueryArgumentSourceCompiler;
use Rokke\Http\Build\RouteBuildPass;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Build\RouteParameterArgumentSourceCompiler;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\Emitter\EmitterInterface;
use Rokke\Runtime\Build\ArgumentPlanCompiler;
use Rokke\Runtime\Build\DiscoveryEngine;
use Rokke\Runtime\Build\FactoryCompiler;
use Rokke\Runtime\Build\FactoryRepository;
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
use Rokke\Runtime\Compiled\CompiledConfigurationRepository;
use Rokke\Runtime\Compiled\CompiledExecutionPipeline;
use Rokke\Runtime\Compiled\CompiledInterceptorPipeline;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;
use Rokke\Runtime\Extension\ExtensionBuilder;
use Rokke\Runtime\Extension\ExtensionRegistry;

/**
 * Composition root for HTTP applications built from extensions.
 *
 * Wires the HTTP build pipeline (HttpCapabilityPass, RouteBuildPass) together
 * with the standard runtime pipeline (OperationModelBuilderPass, DiscoveryEngine)
 * and produces an HttpHost ready to serve requests.
 *
 * Usage:
 *   (new HttpKernel())
 *       ->register(new HttpExtension(__DIR__ . '/app/Handler', 'App\Handler', host: '0.0.0.0', port: 8080))
 *       ->build()
 *       ->run();
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

		// Configuration descriptors are added to ApplicationModel for BuildPasses to read
		foreach ($extensionBuilder->getConfigurationDescriptors() as $descriptor) {
			$model->add($descriptor);
		}

		$routeBuildPass = new RouteBuildPass();
		$routeTree      = $routeBuildPass->compile($model->definitions(RouteDescriptor::class));

		$serviceDescriptors = $model->definitions(ServiceDescriptor::class);
		$registeredImpls    = array_map(static fn (ServiceDescriptor $d): string => $d->implementation, $serviceDescriptors);
		$handlerDescriptors = [];

		foreach ($model->definitions(OperationDefinition::class) as $definition) {
			$class = $definition->handler;
			if (!in_array($class, $registeredImpls, true)) {
				$reflection = new \ReflectionClass($class);
				if (!$reflection->hasMethod('__invoke') || !$reflection->getMethod('__invoke')->isPublic()) {
					throw new \RuntimeException("Handler {$class} must declare a public __invoke() method.");
				}
				$handlerDescriptors[] = new ServiceDescriptor($class, $class, [$class]);
				$registeredImpls[]    = $class;
			}
		}

		$factories = FactoryRepository::build([...$serviceDescriptors, ...$handlerDescriptors], new FactoryCompiler());

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
		$argumentPlans     = [];
		$resultPlans       = [];
		$validationPlans   = [];
		$behaviorPipelines = [];
		$compiledOps       = [];

		foreach ($model->definitions(OperationDefinition::class) as $index => $definition) {
			$factoryId               = $factories->id($definition->handler)
				?? throw new \RuntimeException("Handler {$definition->handler} not found in factory repository.");
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
				factoryId: $factoryId,
				argumentPlanId: $index,
				resultPlanId: $index,
				behaviorPipelineId: $behaviorPipelineId,
				validationPlanId: $index,
			);
		}

		$executionPipeline = new CompiledExecutionPipeline(
			factories: $factories,
			argumentPlans: $argumentPlans,
			resultPlans: $resultPlans,
			behaviorPipelines: $behaviorPipelines,
			validationPlans: $validationPlans,
		);

		$configuredArtifacts = [];
		foreach ($this->extensions->getBuildPasses() as $pass) {
			foreach ($pass->process($model) as $artifact) {
				$configuredArtifacts[] = $artifact;
			}
		}
		$configurations = CompiledConfigurationRepository::build($configuredArtifacts);

		$runtime = new CompiledRuntime(
			executionPipeline: $executionPipeline,
			interceptorPipeline: CompiledInterceptorPipeline::empty(),
			operations: OperationRepository::build($compiledOps),
			factories: $factories,
			artifacts: ArtifactRepository::build([CompiledRouteTree::class => $routeTree]),
			configurations: $configurations,
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

	public function run(): void
	{
		$this->host()->run();
	}
}
