<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\EventModel;

class EventPresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	public function renderDefault($slug): void
	{
		$this->template->event = $this->eventModel->getEvent($slug);
	}

	public function renderGallery($slug): void
	{
		$this->template->event = $this->eventModel->getEvent($slug);
	}

	public function renderResults($slug): void
	{
		$this->template->event = $this->eventModel->getEvent($slug);
	}

	public function renderRegistration($slug): void
	{
		$this->template->event = $this->eventModel->getEvent($slug);
	}

}
