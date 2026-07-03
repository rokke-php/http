<?php

declare(strict_types=1);

namespace Rokke\Http;

use Rokke\Http\Attribute\HttpMethodAttribute;
use Rokke\Http\Build\HttpCapability;
use Rokke\Http\Build\HttpCapabilityPass;
use Rokke\Http\Build\RouteCompiler;
use Rokke\Http\Build\RouteDescriptor;
use Rokke\Http\Compiled\CompiledRouteTree;
use Rokke\Http\Emitter\EmitterInterface;
use Rokke\Runtime\Build\ArgumentPlanCompiler;
use Rokke\Runtime\Build\FactoryRepository;
use Rokke\Runtime\Build\ModelBuilder;
use Rokke\Runtime\Build\OperationCapability;
use Rokke\Runtime\Build\OperationDefinition;
use Rokke\Runtime\Build\OperationModelBuilderPass;
use Rokke\Runtime\Build\ResultPlanCompiler;
use Rokke\Runtime\Compiled\ArtifactRepository;
use Rokke\Runtime\Compiled\CompiledOperation;
use Rokke\Runtime\Compiled\CompiledRuntime;
use Rokke\Runtime\Compiled\OperationRepository;

final class HttpApplication
{
	/** @var list<class-string> */
	private array $handlerClasses = [];

	public static function create(): self
	{
		return new self();
	}

	/** @param class-string $handlerClass */
	public function register(string $handlerClass): self
	{
		$this->handlerClasses[] = $handlerClass;

		return $this;
	}

	public function build(?EmitterInterface $emitter = null): HttpHost
	{
		$capabilities = [];

		foreach ($this->handlerClasses as $handlerClass) {
			$attr        = $this->resolveHttpAttribute($handlerClass);
			$operationId = $this->operationId($handlerClass);
			$instance    = new $handlerClass();

			if (!is_callable($instance)) {
				throw new \InvalidArgumentException(
					"Handler {$handlerClass} must declare an __invoke method.",
				);
			}

			$capabilities[] = new HttpCapability($attr->method(), $attr->path, $operationId);
			$capabilities[] = new OperationCapability($operationId, $operationId, $instance);
		}

		$modelBuilder = new ModelBuilder([
			new HttpCapabilityPass(),
			new OperationModelBuilderPass(),
		]);
		$model = $modelBuilder->build($capabilities);

		$compiler      = new RouteCompiler();
		$routeTree     = $compiler->compile($model->definitions(RouteDescriptor::class));
		$factories     = FactoryRepository::empty();
		$argCompiler   = new ArgumentPlanCompiler();
		$resultCompiler = new ResultPlanCompiler();
		$handlers      = [];
		$argumentPlans = [];
		$resultPlans   = [];
		$compiledOps   = [];

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

		return new HttpHost($runtime, $emitter);
	}

	/** @param class-string $handlerClass */
	private function resolveHttpAttribute(string $handlerClass): HttpMethodAttribute
	{
		$reflection = new \ReflectionClass($handlerClass);
		$attrs      = $reflection->getAttributes(HttpMethodAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

		if ($attrs === []) {
			throw new \InvalidArgumentException(
				"Class {$handlerClass} has no HTTP method attribute (#[Get], #[Post], #[Put], #[Patch], #[Delete]).",
			);
		}

		return $attrs[0]->newInstance();
	}

	/** @param class-string $handlerClass */
	private function operationId(string $handlerClass): string
	{
		$pos = strrpos($handlerClass, '\\');

		return $pos === false ? $handlerClass : substr($handlerClass, $pos + 1);
	}
}
