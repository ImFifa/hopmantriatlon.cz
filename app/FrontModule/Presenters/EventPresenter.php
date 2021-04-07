<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\CompetitorModel;
use App\Model\EventModel;
use K2D\File\Model\FileModel;
use K2D\Gallery\Models\GalleryModel;
use K2D\Gallery\Models\ImageModel;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Ublaboo\DataGrid\DataGrid;

class EventPresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public CompetitorModel $competitorModel;

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
			$this->template->participants = $this->competitorModel->getRegisteredCompetitors($event->id);
			$this->template->participantsMan = $this->competitorModel->getRegisteredMan($event->id);
			$this->template->participantsWoman = $this->competitorModel->getRegisteredWoman($event->id);
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
	}


	public function renderGallery($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
		}
	}


	public function renderRegistration($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
		}
	}


	public function renderStartlist($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
			$this->template->competitors = $this->competitorModel->getCompetitors($event->id);
		}
	}


	protected function createComponentSignUpForm(): Form
	{
		$form = new Form();

		$form->addHidden('id');

		$form->addText('event_id', 'ID události');

		$form->addText('name', 'Jméno')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Musíte uvést Vaše jméno');

		$form->addText('surname', 'Příjmení')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Musíte uvést Vaše příjmení');

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

		$form->addInteger('year_of_birth', 'Rok narození')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka jsou %s znaky', 4)
			->addRule(Form::MAX,'Děti mladší 15ti let se nemohou zaregistrovat.',2003)
			->addRule(Form::MIN,'Vážně je Vám víc než 100 let? :-)',1921)
			->setRequired('Musíte uvést Váš rok narození');

		$form->addText('category', 'Kategorie')
			->setHtmlAttribute('placeholder','Zadejte rok narození a pohlaví')
			->setDisabled()
			->setRequired('Pro správné zařazení do kategorie je nutné vyplnit rok narození a pohlaví.');

		$form->addText('team', 'Oddíl/město')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 150);

		$form->addEmail('email', 'Emailová adresa')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Musíte uvést Vaši emailovou adresu');

		$form->addCheckbox('agree', 'Souhlasím s využitím osobních údajů za účelem zpracování výsledků závodu.')
			->setHtmlAttribute('class', 'form-control')
			->setRequired('Je potřeba souhlasit s podmínkami');


//		$form->addInvisibleReCaptcha('recaptcha')
//			->setMessage('Jste opravdu člověk?');

		$form->addSubmit('submit', 'Odeslat přihlášku');

		$form->onSubmit[] = function (Form $form) {
			$values = $form->getValues(true);

			if ($values['id'] === '') {
				unset($values['id']);
				unset($values['agree']);

				$year = $values['year_of_birth'];
				if ($values['sex'] != 'Ž') {
					if ($year >= 2003) {
						$values['category'] = 'Dorostenci';
					} elseif ($year >= 1982) {
						$values['category'] = 'Muži do 40ti let';
					} elseif ($year >= 1972) {
						$values['category'] = 'Muži do 50ti let';
					} elseif ($year >= 1962) {
						$values['category'] = 'Muži do 60ti let';
					} else {
						$values['category'] = 'Muži 60+';
					}
				} else {
					if ($year >= 2003) {
						$values['category'] = 'Dorostenky';
					} elseif ($year >= 1982) {
						$values['category'] = 'Ženy do 40ti let';
					} elseif ($year >= 1972) {
						$values['category'] = 'Ženy do 50ti let';
					} else {
						$values['category'] = 'Ženy 50+';
					}
				}

				$values['id'] = $this->competitorModel->insert($values)->id;
				$this->flashMessage('Registrace proběhla úspěšně!', 'primary');

			} else {
				$this->flashMessage('Během registrace nastala chyba', 'danger');
			}

			$this->redirect('this');
		};

		return $form;
	}
}
