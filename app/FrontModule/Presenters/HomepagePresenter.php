<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\EventModel;
use K2D\Gallery\Models\GalleryModel;
use K2D\Gallery\Models\ImageModel;
use K2D\News\Models\NewModel;

class HomepagePresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public NewModel $newsModel;

	/** @inject */
	public ImageModel $imageModel;

	public function renderDefault(): void
	{
		$this->template->events = $this->eventModel->getActiveEvents();
		$this->template->news = $this->newsModel->getPublicNews('cs')->limit(3);

		$vars = $this->configuration;
		$hopmanNumbersActive = $vars->hopmanNumbersActive;

		if ($hopmanNumbersActive != '0') {
			$this->template->hopmanNumbersActive = $hopmanNumbersActive;
			$this->template->hopmanYears = $vars->hopmanNumbersYears;
			$this->template->hopmanCompetitions = $vars->hopmanNumbersCompetitions;
			$this->template->hopmanParticipants = $vars->hopmanNumbersParticipants;
			$this->template->hopmanBeers = $vars->hopmanNumbersBeers;
		}
	}

	public function renderTeam(): void
	{
		$this->template->images = $this->imageModel->getImagesByGallery(1);
	}

	public function renderContact(): void
	{

	}

	public function renderArchive(): void
	{
		$this->template->events = $this->eventModel->getInactiveEvents();
	}

	public function renderAbout(): void
	{

	}

	public function renderSitemap(): void
	{
		$this->template->events = $this->eventModel->getActiveEvents();
	}

}
