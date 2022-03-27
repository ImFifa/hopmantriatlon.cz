<?php declare(strict_types = 1);

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;

class RouterFactory
{

	use Nette\StaticClass;

	public static function createRouter(): Nette\Routing\Router
	{
		$router = new RouteList();
        $router->withModule('Admin')->addRoute('admin/<presenter>/<action>[/<competition_id>][/<id>]', 'Homepage:default');

        $router->withModule('Front')->addRoute('[<lang=cs (cs)>/]', 'Homepage:default');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]team', 'Homepage:team');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]kontakt', 'Homepage:contact');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]archiv', 'Homepage:archive');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]napsali-o-nas', 'Homepage:about');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]mapa-stranek', 'Homepage:sitemap');

		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]clenove-teamu', 'Member:default');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]clenove-teamu/<slug>', 'Member:show');

		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]aktuality', 'News:default');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]aktualita/<slug>', 'News:show');

		// zatecky cyklista
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]zatecky-cyklista', 'ZateckyCyklista:default');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]zatecky-cyklista/registrace', 'ZateckyCyklista:registration');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]zatecky-cyklista/startovni-listina', 'ZateckyCyklista:startlist');

		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]<slug>', 'Event:default');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]<slug>/galerie', 'Event:gallery');
        $router->withModule('Front')->addRoute('[<lang=cs (cs)>/]<slug>/registrace', 'Event:registration');
        $router->withModule('Front')->addRoute('[<lang=cs (cs)>/]<slug>/odeslano', 'Event:registrationSent');
		$router->withModule('Front')->addRoute('[<lang=cs (cs)>/]<slug>/startovni-listina', 'Event:startlist');

		$router->withModule('Front')->addRoute('[<lang=cs [a-z]{2}>/]<presenter>/<action>', 'Error:404');
		return $router;
	}

}
