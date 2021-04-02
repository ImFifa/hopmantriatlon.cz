<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\EventModel;
use K2D\News\Models\NewModel;

class HomepagePresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public NewModel $newsModel;

	public function renderDefault(): void
	{
		$this->template->events = $this->eventModel->getEvents();
		$this->template->news = $this->newsModel->getPublicNews('cs')->limit(3);

		$vars = $this->configuration;
		$this->template->hopmanYears = $vars->hopmanNumbersYears;
		$this->template->hopmanCompetitions = $vars->hopmanNumbersCompetitions;
		$this->template->hopmanParticipants = $vars->hopmanNumbersParticipants;
		$this->template->hopmanBeers = $vars->hopmanNumbersBeers;
	}

	public function renderTeam(): void
	{

	}

	public function renderContact(): void
	{

	}

	public function renderArchive(): void
	{

	}

}
