<?php

namespace App\AdminModule\Presenters;

use App\Model\EventModel;
use K2D\Core\AdminModule\Presenter\BasePresenter;

class EventPresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	public function renderDefault(): void
	{
		$this->template->events = $this->eventModel->getEvents();
		bdump($this->template->events);
	}

	public function renderEdit(string $slug): void
	{
		$this->template->event = $this->eventModel->getEvent($slug);
	}

	public function renderRegistered(string $slug): void
	{
		$this->template->event = $this->eventModel->getEvent($slug);
	}


}
