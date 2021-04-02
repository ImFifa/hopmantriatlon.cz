<?php declare(strict_types=1);

namespace App\AdminModule\Dashboard\EventPanel;

use App\Model\EventModel;
use K2D\Core\AdminModule\Component\DashboardControl\Panel;

class EventPanel extends Panel
{

	private EventModel $eventModel;

	public function __construct(EventModel $eventModel)
	{
		$this->eventModel = $eventModel;
	}

	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/EventPanel.latte');
		$this->template->count = $this->eventModel->getCount();
		$this->template->lastNew = $this->eventModel->getTable()->order('updated DESC, id DESC')->limit(1)->fetch();
		$this->template->events = $this->eventModel->getEvents();
		$this->template->render();
	}

}
