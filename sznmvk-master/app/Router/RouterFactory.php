<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
        $router = new RouteList();

        $admin = new RouteList('Admin');
        $admin[] = new Route('admin/<presenter>/<action>[/<id>]', 'Dashboard:default');
        $router[] = $admin;

        $public = new RouteList('Front');
        $public[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
        $router[] = $public;

        return $router;
	}
}
