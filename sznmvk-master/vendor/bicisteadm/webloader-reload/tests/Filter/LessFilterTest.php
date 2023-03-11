<?php
declare(strict_types=1);

namespace WebLoader\Test\Filter;

use PHPUnit\Framework\TestCase;
use WebLoader\Compiler;
use WebLoader\DefaultOutputNamingConvention;
use WebLoader\FileCollection;
use WebLoader\Filter\LessFilter;

class LessFilterTest extends TestCase
{
	/** @var LessFilter */
	private $filter;

	/** @var Compiler */
	private $compiler;


	protected function setUp(): void
	{
		$this->filter = new LessFilter(new \lessc());

		$files = new FileCollection(__DIR__ . '/../fixtures');
		@mkdir($outputDir = __DIR__ . '/../temp/');
		$this->compiler = new Compiler($files, new DefaultOutputNamingConvention(), $outputDir);
	}


	public function testReplace(): void
	{
		$file = __DIR__ . '/../fixtures/style.less';
		$less = $this->filter->__invoke(file_get_contents($file), $this->compiler, $file);
		$this->assertSame(file_get_contents(__DIR__ . '/../fixtures/style.less.expected'), $less);
	}
}
