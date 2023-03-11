<?php

declare(strict_types=1);

namespace CarrooiTests\Menu\LinkGenerator;

use Carrooi\Menu\LinkGenerator\NetteLinkGenerator;
use CarrooiTests\TestCase;
use Mockery\MockInterface;
use Tester\Assert;

require_once __DIR__. '/../../../bootstrap.php';

/**
 * @author David Kudera <kudera.d@gmail.com>
 */
final class NetteLinkGeneratorTest extends TestCase
{


	public function testLink_action(): void
	{
		$netteLinkGenerator = $this->createMockNetteLinkGenerator(function(MockInterface $netteLinkGenerator) {
			$netteLinkGenerator->shouldReceive('link')->andReturn('/');
		});

		$item = $this->createMockMenuItem(function(MockInterface $item) {
			$item->shouldReceive('getAction')->andReturn(':Home:default');
			$item->shouldReceive('getActionParameters')->andReturn([]);
		});

		$linkGenerator = new NetteLinkGenerator($netteLinkGenerator);

		Assert::same('/', $linkGenerator->link($item));
	}


	public function testLink_link(): void
	{
		$netteLinkGenerator = $this->createMockNetteLinkGenerator(function(MockInterface $netteLinkGenerator) {
			$netteLinkGenerator->shouldReceive('link')->andReturn('/');
		});

		$item = $this->createMockMenuItem(function(MockInterface $item) {
			$item->shouldReceive('getAction')->andReturn(null);
			$item->shouldReceive('getLink')->andReturn('/');
		});

		$linkGenerator = new NetteLinkGenerator($netteLinkGenerator);

		Assert::same('/', $linkGenerator->link($item));
	}


	public function testLink(): void
	{
		$netteLinkGenerator = $this->createMockNetteLinkGenerator(function(MockInterface $netteLinkGenerator) {
			$netteLinkGenerator->shouldReceive('link')->andReturn('/');
		});

		$item = $this->createMockMenuItem(function(MockInterface $item) {
			$item->shouldReceive('getAction')->andReturn(null);
			$item->shouldReceive('getLink')->andReturn(null);
		});

		$linkGenerator = new NetteLinkGenerator($netteLinkGenerator);

		Assert::same('#', $linkGenerator->link($item));
	}

}

(new NetteLinkGeneratorTest)->run();
