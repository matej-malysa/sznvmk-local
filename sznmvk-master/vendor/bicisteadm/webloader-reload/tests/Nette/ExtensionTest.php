<?php
declare(strict_types=1);

namespace WebLoader\Test\Nette;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\Utils\Finder;
use PHPUnit\Framework\TestCase;
use WebLoader\Nette\Extension;
use WebLoader\Path;


class ExtensionTest extends TestCase
{

	/** @var Container */
	private $container;


	private function prepareContainer($configFiles): void
	{
		$tempDir = __DIR__ . '/../temp';
		foreach (Finder::findFiles('*')->exclude('.gitignore')->from($tempDir . '/cache') as $file) {
			unlink((string) $file);
		}

		$configurator = new Configurator();
		$configurator->setTempDirectory($tempDir);

		foreach ($configFiles as $file) {
			$configurator->addConfig($file);
		}

		$configurator->addParameters([
			'wwwDir' => __DIR__ . '/..',
			'fixturesDir' => __DIR__ . '/../fixtures',
			'tempDir' => $tempDir,
		]);

		$extension = new Extension(__DIR__ . '/..', $configurator->isDebugMode());
		$extension->install($configurator);

		$this->container = @$configurator->createContainer(); // sends header X-Powered-By, ...
	}


	public function testJsCompilerService(): void
	{
		$this->prepareContainer([__DIR__ . '/../fixtures/extension.neon']);
		$this->assertInstanceOf('WebLoader\Compiler', $this->container->getService('webloader.jsDefaultCompiler'));
	}


	public function testExcludeFiles(): void
	{
		$this->prepareContainer([__DIR__ . '/../fixtures/extension.neon']);
		$files = $this->container->getService('webloader.jsExcludeCompiler')->getFileCollection()->getFiles();

		$this->assertTrue(in_array(Path::normalize(__DIR__ . '/../fixtures/a.txt'), $files, true));
		$this->assertFalse(in_array(Path::normalize(__DIR__ . '/../fixtures/dir/one.js'), $files, true));
	}


	public function testJoinFilesOn(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionJoinFilesTrue.neon',
		]);
		$this->assertTrue($this->container->getService('webloader.jsDefaultCompiler')->getJoinFiles());
	}


	public function testJoinFilesOff(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionJoinFilesFalse.neon',
		]);
		$this->assertFalse($this->container->getService('webloader.jsDefaultCompiler')->getJoinFiles());
	}


	public function testJoinFilesOffInOneService(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
		]);
		$this->assertFalse($this->container->getService('webloader.cssJoinOffCompiler')->getJoinFiles());
	}


	public function testAsyncOn(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionAsyncTrue.neon',
		]);
		$this->assertTrue($this->container->getService('webloader.jsDefaultCompiler')->isAsync());
	}


	public function testAsyncOff(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionAsyncFalse.neon',
		]);
		$this->assertFalse($this->container->getService('webloader.jsDefaultCompiler')->isAsync());
	}


	public function testDeferOn(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionDeferTrue.neon',
		]);
		$this->assertTrue($this->container->getService('webloader.jsDefaultCompiler')->isDefer());
	}


	public function testDeferOff(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionDeferFalse.neon',
		]);
		$this->assertFalse($this->container->getService('webloader.jsDefaultCompiler')->isDefer());
	}


	public function testAbsoluteUrlOn(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionAbsoluteUrlTrue.neon',
		]);
		$this->assertTrue($this->container->getService('webloader.jsDefaultCompiler')->isAbsoluteUrl());
	}


	public function testAbsoluteUrlOff(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionAbsoluteUrlFalse.neon',
		]);
		$this->assertFalse($this->container->getService('webloader.jsDefaultCompiler')->isAbsoluteUrl());
	}


	public function testNonceSet(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionNonce.neon',
		]);
		$this->assertEquals('rAnd0m123', $this->container->getService('webloader.jsDefaultCompiler')->getNonce());
	}


	public function testNonceNotSet(): void
	{
		$this->prepareContainer([
			__DIR__ . '/../fixtures/extension.neon',
		]);
		$this->assertNull($this->container->getService('webloader.jsDefaultCompiler')->getNonce());
	}


	public function testExtensionName(): void
	{
		$tempDir = __DIR__ . '/../temp';
		$class = 'ExtensionNameServiceContainer';

		$configurator = new Configurator();
		$configurator->setTempDirectory($tempDir);
		$configurator->addParameters(['container' => ['class' => $class]]);
		$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) {
			$extension = new Extension(__DIR__ . '/..', $configurator->isDebugMode());
			$compiler->addExtension('Foo', $extension);
		};
		$configurator->addConfig(__DIR__ . '/../fixtures/extensionName.neon');
		$container = $configurator->createContainer();

		$this->assertInstanceOf('WebLoader\Compiler', $container->getService('Foo.cssDefaultCompiler'));
		$this->assertInstanceOf('WebLoader\Compiler', $container->getService('Foo.jsDefaultCompiler'));
	}
}
