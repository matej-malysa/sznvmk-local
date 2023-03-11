<?php

declare(strict_types=1);

namespace CarrooiTests;

use Carrooi\Menu\AbstractMenuItemsContainer;
use Carrooi\Menu\IMenu;
use Carrooi\Menu\IMenuItem;
use Carrooi\Menu\IMenuItemFactory;
use Carrooi\Menu\LinkGenerator\ILinkGenerator;
use Carrooi\Menu\Loaders\IMenuLoader;
use Carrooi\Menu\Menu;
use Carrooi\Menu\Security\IAuthorizator;
use Mockery\MockInterface;
use Nette\Application\Application;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\Url;
use Nette\Localization\ITranslator;
use Tester;

/**
 * @author David Kudera <kudera.d@gmail.com>
 */
abstract class TestCase extends Tester\TestCase
{


	public function tearDown()
	{
		\Mockery::close();
	}


	protected function createMockMenu(callable $fn = null): IMenu
	{
		return $this->createMock(IMenu::class, $fn);
	}


	protected function createMockMenuItem(callable $fn = null): IMenuItem
	{
		return $this->createMock(IMenuItem::class, $fn);
	}


	protected function createMockMenuItemFactory(callable $fn = null): IMenuItemFactory
	{
		return $this->createMock(IMenuItemFactory::class, $fn);
	}


	protected function createPartialMockAbstractMenuItemsContainer(callable $fn = null, array $args = null): AbstractMenuItemsContainer
	{
		return $this->createMock(AbstractMenuItemsContainer::class, $fn, $args, true);
	}


	protected function createMockMenuLoader(callable $fn = null): IMenuLoader
	{
		return $this->createMock(IMenuLoader::class, $fn);
	}


	protected function createMockLinkGenerator(callable $fn = null): ILinkGenerator
	{
		return $this->createMock(ILinkGenerator::class, $fn);
	}


	protected function createMockNetteLinkGenerator(callable $fn = null): LinkGenerator
	{
		return $this->createMock(LinkGenerator::class, $fn);
	}


	protected function createMockTranslator(callable $fn = null): ITranslator
	{
		return $this->createMock(ITranslator::class, $fn);
	}


	protected function createMockAuthorizator(callable $fn = null): IAuthorizator
	{
		return $this->createMock(IAuthorizator::class, $fn);
	}


	protected function createMockApplication(callable $fn = null): Application
	{
		return $this->createMock(Application::class, $fn);
	}


	protected function createMockPresenter(callable $fn = null): Presenter
	{
		return $this->createMock(Presenter::class, $fn);
	}


	protected function createMockHttpRequest(callable $fn = null): Request
	{
		return $this->createMock(Request::class, $fn);
	}


	protected function createMockHttpUrl(callable $fn = null): Url
	{
		return $this->createMock(Url::class, $fn);
	}


	private function createMock(string $type, callable $fn = null, array $args = null, bool $partial = false)
	{
		$mock = $args === null ? \Mockery::mock($type) : \Mockery::mock($type, $args);

		if ($partial) {
			$mock->makePartial();
		}

		if ($fn) {
			$fn($mock);
		}

		return $mock;
	}

}
