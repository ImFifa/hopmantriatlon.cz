<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\CompetitionModel;
use App\Model\EventCategoryModel;
use App\Model\CompetitorModel;
use App\Model\EventGalleryModel;
use App\Model\EventModel;
use App\Model\RelayModel;
use K2D\File\Model\FileModel;
use K2D\Gallery\Models\ImageModel;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Database\DriverException;
use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;
use Nette\Neon\Neon;

class EventPresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public CompetitorModel $competitorModel;

	/** @inject */
	public RelayModel $relayModel;

	/** @inject */
	public CompetitionModel $competitionModel;

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

		// competition
		if ($event->registration_active) {
			$currYear = date('Y');
			$this->template->competitions = $this->competitionModel->getThisYearsActiveCompetitionsById($event->id, $currYear);
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
			$this->template->galleries = $this->eventGalleryModel->getPublicEventGalleriesFromYear($event->id, $event->gallery_year);
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
				$this->template->galleries = $this->eventGalleryModel->getPublicEventGalleries($event->id);
			}
		}
	}


	public function renderRegistration($slug): void
	{
		$slugArr = explode('-', $slug);
		$slug = $slugArr[0];
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$urlPath = explode('/', $_SERVER['REQUEST_URI']);
			$currYear = date('Y');
			$this->template->event = $event;
			$this->template->categories = $this->eventCategoryModel->getCategoriesForEventById($event->id);
			$this->template->competition = $this->competitionModel->getSelectedCompetition($urlPath[1]);
			$this->template->competitions = $this->competitionModel->getThisYearsActiveCompetitionsById($event->id, $currYear);

		}
	}

	public function renderStartlist($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$this->template->event = $event;
			$this->template->categories = $this->eventCategoryModel->getCategoriesForEventById($event->id);
			$this->template->distances = $this->eventCategoryModel->getDistancesForEventById($event->id);

			bdump($this->template->distances);

			$this->template->competitors = $this->competitorModel->getCompetitors($event->competition_id);

			// render relay startlist
			$this->template->relays = $this->relayModel->getRelays($event->competition_id);
		}
	}


	protected function createComponentHalfmarathonSignUpForm(): Form
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
			->setItems($this->eventCategoryModel->getForSelectByEventId(1))
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

				// get competition name
				$competition = $this->competitionModel->getCompetitionById($values['competition_id']);
				$category = $this->eventCategoryModel->getCategoryById($values['category_id']);
				if ($competition != NULL) {
					$competition_name = $competition->name;
					$event_slug = $competition->slug;
					$category_name = $category->name;
					$sex = ($values['sex'] === 'M') ? 'Muži' : 'Ženy';

					// send mail
					$latte = new Engine;
					$params = [
						'competition_name' => $competition_name,
						'name' => $values['name'],
						'surname' => $values['surname'],
						'sex' => $sex,
						'birth_year' => $values['year_of_birth'],
						'category' => $category_name,
						'distance' => $values['distance'],
						'team' => $values['team']
					];

					$mail = new Message();

					$mail->setFrom('info@hopmantriatlon.cz', 'Hopman');
					$mail->addTo($values['email']);
					$mail->setHtmlBody(
						$latte->renderToString(__DIR__ . '/../../Email/' . $event_slug . '.latte', $params),
						__DIR__ . '/../../assets/img/email');
					$parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));


					$mailer = new SmtpMailer([
						'host' => $parameters['mail']['host'],
						'username' => $parameters['mail']['username'],
						'password' => $parameters['mail']['password'],
						'secure' => $parameters['mail']['secure'],
					]);
					$mailer->send($mail);
				}

				$this->flashMessage('Registrace proběhla úspěšně!');
				$this->redirect('this?odeslano=1');

			} catch (DriverException $e) {
				$this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Kontaktujte prosím správce webu na info@hopmantriatlon.cz', 'danger');
			}
		};

		return $form;
	}


	protected function createComponentTriathlonSignUpForm(): Form
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
				'Dětský triatlon' => 'Dětský triatlon',
				'Sprint triatlon' => 'Sprint triatlon',
				'Olympijský triatlon' => 'Olympijský triatlon'
			]);

		$form->addSelect('category', 'Kategorie')
			->setPrompt('-------')
			->setItems($this->eventCategoryModel->getForSelectByEventId(3))
			->setDisabled();
		$form->addHidden('category_id')
			->setHtmlAttribute('id', 'frm-triathlonSignUpForm-category_id');

		$form->addText('team', 'Oddíl/město')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 150);

		$form->addEmail('email', 'Emailová adresa')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Musíte uvést Vaši emailovou adresu');

		$form->addCheckbox('agree', 'Souhlasím s podmínkami závodu a využitím osobních údajů za účelem zpracování výsledků závodu.')
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

				// get competition name
				$competition = $this->competitionModel->getCompetitionById($values['competition_id']);
				$category = $this->eventCategoryModel->getCategoryById($values['category_id']);
				if ($competition != NULL) {
					$competition_name = $competition->name;
					$event_slug = $competition->slug;
					$category_name = $category->name;
					$sex = ($values['sex'] === 'M') ? 'Muži' : 'Ženy';

					// payment
					if ($values['distance'] == 'Dětský triatlon')
						$price = 50;
					elseif ($values['distance'] == 'Sprint triatlon')
						$price = 400;
					else {
						$price = 500;
					}

					$variableSymbol = str_pad((string)$values['id'], 8, "0", STR_PAD_LEFT);
					// individual race has 1 in front of variableSymbol
					$variableSymbol = 1 . $variableSymbol;

					// send mail
					$latte = new Engine;
					$params = [
						'competition_name' => $competition_name,
						'name' => $values['name'],
						'surname' => $values['surname'],
						'sex' => $sex,
						'birth_year' => $values['year_of_birth'],
						'category' => $category_name,
						'distance' => $values['distance'],
						'team' => $values['team'],
						'price' => $price,
						'variableSymbol' => $variableSymbol,
						'message' => $values['name'] . ' ' . $values['surname'] . ' (' . $values['year_of_birth'] . ') - startovné na: ' . $competition_name
					];

					$mail = new Message();

					$mail->setFrom('info@hopmantriatlon.cz', 'Hopman');
					$mail->addTo($values['email']);
					$mail->setHtmlBody(
						$latte->renderToString(__DIR__ . '/../../Email/' . $event_slug . '.latte', $params),
						__DIR__ . '/../../assets/img/email');
					$parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));

					$mailer = new SmtpMailer([
						'host' => $parameters['mail']['host'],
						'username' => $parameters['mail']['username'],
						'password' => $parameters['mail']['password'],
						'secure' => $parameters['mail']['secure'],
					]);
					$mailer->send($mail);
				}

				$this->flashMessage('Registrace proběhla úspěšně!');
				$this->redirect('this?odeslano=1');

			} catch (DriverException $e) {
				$this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Kontaktujte prosím správce webu na info@hopmantriatlon.cz', 'danger');
			}
		};

		return $form;
	}


	protected function createComponentTriathlonRelaySignUpForm(): Form
	{
		$form = new Form();

		$form->addText('competition_id', 'ID události');

		$form->addText('name', 'Název štafety')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 160)
			->setRequired('Štafeta musí mít nějaký název');

		$form->addText('competitor1', 'Závodník 1')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 160)
			->setRequired('Musíte uvést jméno 1. závodníka');

		$form->addText('competitor2', 'Závodník 2')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 160)
			->setRequired('Musíte uvést jméno 2. závodníka');

		$form->addText('competitor3', 'Závodník 3')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 160)
			->setRequired('Musíte uvést jméno 3. závodníka');

		$form->addEmail('email', 'Kontaktní emailová adresa')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Musíte uvést kontaktní emailovou adresu');

		$form->addCheckbox('agree', 'Souhlasím s podmínkami závodu a využitím osobních údajů za účelem zpracování výsledků závodu.')
			->setHtmlAttribute('class', 'form-control')
			->setRequired('Je potřeba souhlasit s podmínkami');

		$form->addInvisibleReCaptcha('recaptcha')
			->setMessage('Jste opravdu člověk?');

		$form->addSubmit('submit', 'Odeslat přihlášku');

		$form->onSubmit[] = function (Form $form) {
			try {
				$values = $form->getValues(true);

				unset($values['agree']);
				$values['id'] = $this->relayModel->insert($values)->id;

				// get competition name
				$competition = $this->competitionModel->getCompetitionById($values['competition_id']);
				if ($competition != NULL) {
					$competition_name = $competition->name;
					$event_slug = $competition->slug;

					// payment
					$price = 600;
					$variableSymbol = str_pad((string)$values['id'], 8, "0", STR_PAD_LEFT);
					// relay has 2 in front of variableSymbol
					$variableSymbol = 2 . $variableSymbol;

					// send mail
					$latte = new Engine;
					$params = [
						'competition_name' => $competition_name,
						'name' => $values['name'],
						'competitor1' => $values['competitor1'],
						'competitor2' => $values['competitor2'],
						'competitor3' => $values['competitor3'],
						'price' => $price,
						'variableSymbol' => $variableSymbol,
						'message' => 'Hopman triatlon - štafeta ' . $values['name']
					];

					$mail = new Message();

					$mail->setFrom('info@hopmantriatlon.cz', 'Hopman');
					$mail->addTo($values['email']);
					$mail->setHtmlBody(
						$latte->renderToString(__DIR__ . '/../../Email/triatlon-stafeta.latte', $params),
						__DIR__ . '/../../assets/img/email');
					$parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));

					$mailer = new SmtpMailer([
						'host' => $parameters['mail']['host'],
						'username' => $parameters['mail']['username'],
						'password' => $parameters['mail']['password'],
						'secure' => $parameters['mail']['secure'],
					]);
					$mailer->send($mail);
				}

				$this->flashMessage('Registrace proběhla úspěšně!');
				$this->redirect('this?odeslano=1');

			} catch (DriverException $e) {
				$this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Kontaktujte prosím správce webu na info@hopmantriatlon.cz', 'danger');
			}
		};

		return $form;
	}


	protected function createComponentAdventSignUpForm(): Form
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
			->setItems([
				'4,4km' => '4,4km (2 okruhy)',
				'6,6km' => '6,6km (3 okruhy)'
			])
			->setDisabled();

		$form->addSelect('category', 'Kategorie')
			->setPrompt('-------')
			->setItems($this->eventCategoryModel->getForSelectByEventId(5))
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

				// get competition name
				$competition = $this->competitionModel->getCompetitionById($values['competition_id']);
				$category = $this->eventCategoryModel->getCategoryById($values['category_id']);
				if ($competition != NULL) {
					$competition_name = $competition->name;
					$event_slug = $competition->slug;
					$category_name = $category->name;
					$sex = ($values['sex'] === 'M') ? 'Muži' : 'Ženy';

					// send mail
					$latte = new Engine;
					$params = [
						'competition_name' => $competition_name,
						'name' => $values['name'],
						'surname' => $values['surname'],
						'sex' => $sex,
						'birth_year' => $values['year_of_birth'],
						'category' => $category_name,
						'distance' => $values['distance'],
						'team' => $values['team'],
						'message' => 'Hopman triatlon - startovné (' . $values['name'] . ' ' . $values['surname'] . ')'
					];

					$mail = new Message();

					$mail->setFrom('info@hopmantriatlon.cz', 'Hopman');
					$mail->addTo($values['email']);
					$mail->setHtmlBody(
						$latte->renderToString(__DIR__ . '/../../Email/' . $event_slug . '.latte', $params),
						__DIR__ . '/../../assets/img/email');
					$parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));


					$mailer = new SmtpMailer([
						'host' => $parameters['mail']['host'],
						'username' => $parameters['mail']['username'],
						'password' => $parameters['mail']['password'],
						'secure' => $parameters['mail']['secure'],
					]);
					$mailer->send($mail);
				}

				$this->flashMessage('Registrace proběhla úspěšně!');
				$this->redirect('this?odeslano=1');

			} catch (DriverException $e) {
				$this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Kontaktujte prosím správce webu na info@hopmantriatlon.cz', 'danger');
			}
		};

		return $form;
	}
}
