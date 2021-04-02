<?php declare(strict_types=1);

namespace App\AdminModule\Dashboard\EventPanel;

use App\AdminModule\Dashboard\EventPanel\EventPanel;

interface EventPanelFactory
{

	public function create(): EventPanel;

}
