<?php

declare(strict_types = 1);

namespace WebLoader\Filter;

use CoffeeScript\Compiler;
use Throwable;
use WebLoader\WebLoaderException;

/**
 * Coffee script filter implements with composer php compiler
 *
 * @author Jan Svantner
 */
class PHPCoffeeScriptFilter
{
	public function __invoke(string $code, \WebLoader\Compiler $loader, ?string $file = null): string
	{
		$file = (string) $file;

		if (pathinfo($file, PATHINFO_EXTENSION) === 'coffee') {
			$code = $this->compileCoffee($code, $file);
		}

		return $code;
	}


	public function compileCoffee(string $source, ?string $file): string
	{
		try {
			return Compiler::compile($source, ['filename' => $file]);
		} catch (Throwable $e) {
			throw new WebLoaderException('CoffeeScript Filter Error: ' . $e->getMessage(), 0, $e);
		}
	}
}
