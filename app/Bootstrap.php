<?php declare(strict_types = 1);

namespace App;

use Nette\Configurator;
use OriNette\DI\Boot\Environment;
use Symfony\Component\Dotenv\Dotenv;
use function define;
use function dirname;
use function explode;
use function file_exists;

class Bootstrap
{

	public static function boot(): Configurator
	{
		if (file_exists(__DIR__ . '/../.env')) {
			$dotenv = new Dotenv();
			$dotenv->load(__DIR__ . '/../.env');
		}

		define('WWW', dirname(__DIR__) . '/www');

		$configurator = new Configurator();
		$configurator->setTempDirectory(__DIR__ . '/../temp');

		$configurator->addParameters(Environment::loadEnvParameters());

		$debugCookiesVar = $_SERVER['DEBUG_COOKIE_VALUES'] ?? '';
		$debugCookieValues = explode(',', $debugCookiesVar);
		$configurator->setDebugMode(
			Environment::isEnvDebugMode() ||
			Environment::isLocalhost() ||
			Environment::hasCookie($debugCookieValues),
		);
		$configurator->enableDebugger(__DIR__ . '/../log');

		$configurator->addConfig(__DIR__ . '/../vendor/simple-cms/core-module/config/config.neon');
		$configurator->addConfig(__DIR__ . '/../vendor/simple-cms/box-module/config/config.neon');
		$configurator->addConfig(__DIR__ . '/../vendor/simple-cms/file-module/config/config.neon');

		$configurator->addConfig(__DIR__ . '/config/config.neon');
		$configurator->addConfig(__DIR__ . '/config/server/local.neon');
		$configurator->addParameters([
			'rootDir' => dirname(__DIR__),
			'wwwDir' => dirname(__DIR__) . '/www',
		]);

		return $configurator;
	}

}
