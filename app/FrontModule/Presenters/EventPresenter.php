<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\EventCategoryModel;
use App\Model\CompetitorModel;
use App\Model\EventGalleryModel;
use App\Model\EventModel;
use K2D\File\Model\FileModel;
use K2D\Gallery\Models\GalleryModel;
use K2D\Gallery\Models\ImageModel;
use Nette\Application\UI\Form;
use Nette\Database\DriverException;
use Nette\Database\Table\ActiveRow;
use Ublaboo\DataGrid\DataGrid;

class EventPresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public CompetitorModel $competitorModel;

	/** @inject */
	public EventGalleryModel $eventGalleryModel;

	/** @inject */
	public EventCategoryModel $eventCategoryModel;

	/** @inject */
	public ImageModel $imageModel;

	/** @inject */
	public FileModel $fileModel;

	public function renderDefault($slug): void
	{
		// event
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
		}

		// startlist
		if($event->startlist_active) {
			$this->template->participants = $this->competitorModel->getRegisteredCompetitors($event->competition_id);
			$this->template->participantsMan = $this->competitorModel->getRegisteredMan($event->competition_id);
			$this->template->participantsWoman = $this->competitorModel->getRegisteredWoman($event->competition_id);
		}

		// results
		$this->template->results = $this->repository->getFilesDESC($event->results_folder_id);

		// maps
		if($event->maps_folder_id != NULL) {
			$this->template->maps = $this->imageModel->getImagesByGallery($event->maps_folder_id);
		}

		// event gallery
		if($event->gallery_id != NULL) {
			$this->template->images = $this->imageModel->getImagesByGallery($event->gallery_id);
		}

		// event last year gallery
		if ($event->gallery_year != NULL) {
			$this->template->galleries = $this->eventGalleryModel->getPublicEventGalleriesFromYear($event->gallery_year);
		}
	}


	public function renderGallery($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
			if ($event->gallery_year != NULL) {
				$this->template->galleries = $this->eventGalleryModel->getPublicEventGalleries();
			}
		}
	}


	public function renderRegistration($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
			$this->template->categories = $this->eventCategoryModel->getCategoriesForEventById($event->id);
			bdump($this->template->categories);
		}
	}


	public function renderStartlist($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
			$this->template->competitors = $this->competitorModel->getCompetitors($event->competition_id);
		}
	}


	protected function createComponentSignUpForm(): Form
	{
		$form = new Form();

		$form->addText('competition_id', 'ID události');

		$form->addText('name', 'Jméno')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Musíte uvést Vaše jméno');

		$form->addText('surname', 'Příjmení')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Musíte uvést Vaše příjmení');

		$form->addInteger('year_of_birth', 'Rok narození')
			->addRule(Form::LENGTH, 'Požadovaná délka jsou %s znaky', 4)
			->addRule(Form::MAX,'Děti mladší 15ti let se nemohou zaregistrovat.',2006)
			->addRule(Form::MIN,'Vážně je Vám víc než 100 let? :-)',1921)
			->setRequired('Musíte uvést Váš rok narození');

		$form->addSelect('sex', 'Pohlaví')
			->setPrompt('-------')
			->setRequired('Musíte uvést Vaše pohlaví')
			->setItems([
				'M' => 'Muž',
				'Ž' => 'Žena'
			]);

		$form->addSelect('distance', 'Trať')
			->setPrompt('-------')
			->setRequired('Musíte si zvolit trať')
			->setItems([
				'Desítka' => 'Desítka (10km)',
				'Půlmaraton' => 'Půlmaraton (21km)'
			]);

		$form->addSelect('category', 'Kategorie')
			->setPrompt('-------')
			->setItems($this->eventCategoryModel->getForSelect())
			->setDisabled();
		$form->addHidden('category_id')
			->setHtmlAttribute('id', 'frm-signUpForm-category_id');

		$form->addText('team', 'Oddíl/město')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 150);

		$form->addEmail('email', 'Emailová adresa')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Musíte uvést Vaši emailovou adresu');

		$form->addCheckbox('agree', 'Souhlasím s využitím osobních údajů za účelem zpracování výsledků závodu.')
			->setHtmlAttribute('class', 'form-control')
			->setRequired('Je potřeba souhlasit s podmínkami');

		$form->addInvisibleReCaptcha('recaptcha')
			->setMessage('Jste opravdu člověk?');

		$form->addSubmit('submit', 'Odeslat přihlášku');

		$form->onSubmit[] = function (Form $form) {
			try {
				$values = $form->getValues(true);

				unset($values['agree']);
				$values['category_id'] = (int) $values['category_id'];
				$values['id'] = $this->competitorModel->insert($values)->id;

				$this->flashMessage('Registrace proběhla úspěšně!');
				$this->redirect('this?odeslano=1');

			} catch (DriverException $e) {
				$this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Kontaktujte prosím správce webu na info@hopmantriatlon.cz', 'danger');
			}
		};

		return $form;
	}
}
