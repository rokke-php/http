<?php

declare(strict_types=1);

namespace Rokke\Http\Discovery;

use Rokke\Contracts\Module\CapabilityInterface;
use Rokke\Contracts\Module\DiscoveryProviderInterface;
use Rokke\Http\Attribute\HttpMethodAttribute;
use Rokke\Http\Build\HttpCapability;
use Rokke\Runtime\Build\OperationCapability;

/**
 * Scans a directory for classes annotated with HTTP method attributes and
 * emits the corresponding capabilities.
 *
 * For each class bearing #[Get], #[Post], #[Put], #[Patch], or #[Delete],
 * two capabilities are emitted:
 *   - HttpCapability  — registers the route in the compiled route tree
 *   - OperationCapability — registers the handler class-string for compilation
 *
 * Files without an HTTP attribute are silently skipped.
 */
final class HttpDirectoryDiscoveryProvider implements DiscoveryProviderInterface
{
	public function __construct(
		private readonly string $directory,
		private readonly string $namespace,
	) {}

	/**
	 * @return list<CapabilityInterface>
	 */
	public function discover(): array
	{
		$capabilities = [];

		foreach ($this->phpFiles() as $file) {
			$class = $this->classFromFile($file);

			if (!class_exists($class)) {
				continue;
			}

			$reflection = new \ReflectionClass($class);
			$attrs      = $reflection->getAttributes(HttpMethodAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

			if ($attrs === []) {
				continue;
			}

			if (!$reflection->hasMethod('__invoke') || !$reflection->getMethod('__invoke')->isPublic()) {
				throw new \InvalidArgumentException(
					"Handler {$class} has an HTTP attribute but does not declare a public __invoke() method.",
				);
			}

			$attr        = $attrs[0]->newInstance();
			$operationId = $this->operationId($class);

			$capabilities[] = new HttpCapability($attr->method(), $attr->path, $operationId);
			$capabilities[] = new OperationCapability($operationId, $operationId, $class);
		}

		return $capabilities;
	}

	/** @return \Traversable<string> */
	private function phpFiles(): \Traversable
	{
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
		);

		foreach ($iterator as $file) {
			if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
				yield $file->getPathname();
			}
		}
	}

	private function classFromFile(string $filePath): string
	{
		$relative        = substr($filePath, strlen($this->directory) + 1);
		$withoutExtension = substr($relative, 0, -4);
		$namespaceSuffix = str_replace(['/', '\\'], '\\', $withoutExtension);

		return rtrim($this->namespace, '\\') . '\\' . $namespaceSuffix;
	}

	/** @param class-string $class */
	private function operationId(string $class): string
	{
		$pos = strrpos($class, '\\');

		return $pos === false ? $class : substr($class, $pos + 1);
	}
}
