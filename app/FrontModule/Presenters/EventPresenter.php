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
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
		}

//		if($event->proposition_id != NULL) {
//			$this->template->files = $this->fileModel->getFiles($event->proposition_id);
//		}

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

	public function renderResults($slug): void
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
			->setPrompt('Žádné')
			->setRequired('Musíte si zvolit pohlaví')
			->setItems([
				'Ž' => 'Žena',
				'M' => 'Muž'
			]);

		$form->addSelect('distance', 'Trať')
			->setPrompt('Žádná')
			->setRequired('Musíte si zvolit trať')
			->setItems([
				'Desítka' => 'Desítka',
				'Půlmaraton' => 'Půlmaraton'
			]);

		$form->addInteger('year_of_birth', 'Rok narození')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka jsou %s znaky', 4)
			->addRule(Form::MAX,'Děti mladší 15ti let se nemohou zaregistrovat.',2003)
			->addRule(Form::MAX,'Vážně je Vám víc než 100 let? :-)',1921)
			->setRequired('Musíte uvést Váš rok narození');

		$form->addText('team', 'Oddíl/město')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 150);

		$form->addEmail('email', 'Emailová adresa')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Musíte uvést Vaši emailovou adresu');

//		$form->addInvisibleReCaptcha('recaptcha')
//			->setMessage('Jste opravdu člověk?');

		$form->addSubmit('submit', 'Odeslat přihlášku');

		$form->onSubmit[] = function (Form $form) {
			$values = $form->getValues(true);

			if ($values['id'] === '') {
				unset($values['id']);

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

	public function createComponentStartList($event_id): DataGrid
	{
		$grid = new DataGrid;

		$grid->setDataSource($this->competitorModel->getCompetitors($event_id));

		$grid->setItemsPerPageList([20, 50, 100], true);

		$grid->addColumnText('id', 'ID')
			->setSortable();

		$grid->addColumnText('name', 'Jméno')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('surname', 'Příjmení')
			->setFilterText();

		$grid->addColumnText('team', 'Oddíl')
			->setFilterText();

		$grid->addColumnDateTime('year_of_birth', 'Rok narození')
			->setFormat('j. n. Y');

		return $grid;
	}

}
