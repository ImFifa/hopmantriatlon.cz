<?php declare(strict_types=1);

if (file_exists($maintenance = __DIR__ . '/.maintenance.active.php')) {
	require $maintenance;
}

require __DIR__ . '/../vendor/autoload.php';

App\Bootstrap::boot()
	->createContainer()
	->getByType(Nette\Application\Application::class)
	->run();
