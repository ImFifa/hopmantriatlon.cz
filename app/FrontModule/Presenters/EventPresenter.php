<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\CompetitionModel;
use App\Model\CategoryModel;
use App\Model\CompetitorModel;
use App\Model\DistanceModel;
use App\Model\EventGalleryModel;
use App\Model\EventModel;
use App\Model\RelayModel;
use K2D\Core\Models\LogModel;
use K2D\File\Model\FileModel;
use K2D\Gallery\Models\ImageModel;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Database\DriverException;
use Nette\Mail\Message;
use Nette\Mail\SmtpException;
use Nette\Mail\SmtpMailer;
use Nette\Neon\Neon;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

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
    public CategoryModel $categoryModel;

    /** @inject */
    public DistanceModel $distanceModel;

    /** @inject */
	public ImageModel $imageModel;

	/** @inject */
	public FileModel $fileModel;

    /** @inject */
    public LogModel $logModel;

    // render event default page with all info
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

			switch ($event->id) {
				case 1:
					$this->template->kids = $this->competitorModel->getRegisteredKids($event->competition_id);
					$this->template->t10k = $this->competitorModel->getRegistered10Run($event->competition_id);
					$this->template->halfmarathon = $this->competitorModel->getRegistered21Run($event->competition_id);
					break;
				case 3:
					$this->template->kids = $this->competitorModel->getRegisteredKids($event->competition_id);
					$this->template->sprint = $this->competitorModel->getRegisteredSprintTriathlon($event->competition_id);
					$this->template->olympic = $this->competitorModel->getRegisteredOlympicTriathlon($event->competition_id);
					$this->template->relays = $this->relayModel->getRegisteredRelays($event->competition_id + 1);
					break;
			}
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

    // render event gallery
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

    // render registration page (without form)
	public function renderRegistration($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
			$urlPath = explode('/', $_SERVER['REQUEST_URI']);
			$currYear = date('Y');
			$this->template->event = $event;
            $categories = $this->categoryModel->getCategories();

            // set categories
            if ($event->slug === 'pulmaraton') {
                unset($categories[8], $categories[9]);
            } else {
                unset($categories[10], $categories[11], $categories[12], $categories[13], $categories[14]);
            }

            $this->template->categories = $categories;
			$this->template->competition = $this->competitionModel->getSelectedCompetition($urlPath[1]);
			$this->template->competitions = $this->competitionModel->getThisYearsActiveCompetitionsById($event->id, $currYear);

		}
	}

    // render startlist page (without startlist table)
	public function renderStartlist($slug): void
	{
		$event = $this->eventModel->getEvent($slug);
		if (!$event) {
			$this->error();
		} else {
            $competition = $this->competitionModel->getLatestCompetition($event->id);
            $this->template->competition = $competition;
            $this->template->event = $event;
		}
	}

    // render registration forms for individual competitions
	protected function createComponentSignUpForm(): Multiplier
    {
        return new Multiplier(function ($competition_id) {

            $competition_id = (int)$competition_id;
            $currentYear = date("Y");
            $form = new Form();

            $form->addText('competition_id', 'ID události');
            $form->addText('address', 'Ochrana proti botům');
            $form->addInteger('category_id_hidden', 'Skryté ID kategorie');

            $form->addText('name', 'Jméno')
                ->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
                ->setRequired('Musíte uvést Vaše jméno');

            $form->addText('surname', 'Příjmení')
                ->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
                ->setRequired('Musíte uvést Vaše příjmení');

            $form->addSelect('distance_id', 'Trať')
                ->setPrompt('-------')
                ->setRequired('Musíte si zvolit trať')
                ->setItems($this->distanceModel->getDistancesByCompetition($competition_id));


            $form->addInteger('year_of_birth', 'Rok narození')
                ->addRule(Form::LENGTH, 'Požadovaná délka jsou %s znaky', 4)
                ->addRule(Form::MIN, 'Vážně je Vám víc než 100 let? :-)', $currentYear - 100)
                // pokud je trat detsky beh
                ->addConditionOn($form['distance_id'], $form::IS_IN, [3,6,10])
                    ->addRule(Form::MIN,'Na dětské závody se mohou přihlásit pouze děti do 14 (na triatlonu do 10) let', $currentYear - 14)
                // pokud je trat desitka
                ->addConditionOn($form['distance_id'], $form::IS_IN, [2,5])
                    ->addRule(Form::MAX,'Na tuto trať se mohou přihlásit pouze závodníci, který je alespoň 15 let', $currentYear - 15)
                // pokud je trat pulmaraton
                ->addConditionOn($form['distance_id'], $form::IS_IN, [3,6,10])
                    ->addRule(Form::MAX,'Na tuto trať se mohou přihlásit pouze závodníci, který je alespoň 18 let', $currentYear - 18)
                ->setRequired('Musíte uvést Váš rok narození');

            $form->addSelect('sex', 'Pohlaví')
                ->setPrompt('-------')
                ->setRequired('Musíte uvést Vaše pohlaví')
                ->setItems([
                    'M' => 'Muž',
                    'Ž' => 'Žena'
                ]);

            $form->addSelect('category_id', 'Kategorie')
                ->setPrompt('-------')
                ->setDisabled()
                ->setItems($this->categoryModel->getForSelect());

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

            $form->addButton('reset')
                ->setHtmlAttribute('type', 'reset')
                ->setCaption('Restartovat formulář');

            $form->onSubmit[] = function (Form $form) {
                try {
                    $currentYear = date("Y");
                    $values = $form->getValues(true);

                    bdump($values);
                    // anti-spam
                    if (!empty($values['address'])) {
                        $this->flashMessage('Boti se do našeho závodu registrovat nemohou. Zkuste to znovu jako člověk.', 'warning');
                        $this->redirect('this?bot=1');
                    }

                    // chybi category_id
                    if (empty($values['category_id_hidden'])) {
                        throw new \InvalidArgumentException('Chybějící hodnota v poli ID kategorie.');
                    }

                    // chybny vek
                    $distance = $this->distanceModel->getDistanceById($values['distance_id']);
                    if ($distance->min_age != NULL && $distance->min_age > ($currentYear - $values['year_of_birth'])) {
                        throw new \InvalidArgumentException($values['name'] . ' ' . $values['surname'] . ' se pokusil přihlásit na '.$distance->name.' jako ročník '.$values['year_of_birth'].'.');
                    }

                    $values['category_id'] = $values['category_id_hidden'];
                    unset($values['category_id_hidden']);
                    unset($values['reset']);
                    unset($values['agree']);
                    unset($values['address']);

                    // insert to database
                    $values['id'] = $this->competitorModel->insert($values)->id;

                    // send confirmation mail
                    $this->sendConfirmationMail($values);

                    // get competition name
                    $competition = $this->competitionModel->getCompetitionById($values['competition_id']);

                    $this->flashMessage('Jsi zaregistrován/a na závod '.$competition->name.'! Na adresu '. $values['email'] .' ti byl právě odeslán potvrzovací email s vyplněnými údaji a informacemi k platbě.');
                    $this->logModel->log('Úspěšná registrace', $competition->name . ' - ' . $values['name'] . ' ' . $values['surname'] . ' se právě zaregistroval.', 'info');
                    $this->redirect('this?odeslano=1');

                } catch (SmtpException $e) {
                    $this->flashMessage('Registrace se nezdařila, protože nebylo možné odeslat potvrzovací email. Zadali jste správnou adresu? Pokud ano, kontaktujte prosím správce webu na info@hopmantriatlon.cz.', 'danger');
                    $this->logModel->log('Nezdařená registrace', $competition->name . ' - ' . $e->getMessage(), 'error', $e->getFile());
                } catch (\InvalidArgumentException $e) {
                    $this->flashMessage('V tomto věku nelze startovat na zvolené trati.', 'danger');
                    $this->logModel->log('Nezdařená registrace', $competition->name . ' - ' . $e->getMessage(), 'error', $e->getFile());
                }
            };

            return $form;
        });
	}

    private function sendConfirmationMail(array $values): void
    {
        $category = $this->categoryModel->getCategoryById($values['category_id']);
        $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
        $distance = $this->distanceModel->getDistanceById($values['distance_id']);

        // setup starting fee
        $price = $distance->price;
        if (($category->name === 'Dorostenci' || $category->name === 'Dorostenky') && $competition->event_id != 3) {
            $price -= 50;
        }

        $variableSymbol = str_pad((string)$values['id'], 5, "0", STR_PAD_LEFT);
        $variableSymbol = 1 . $variableSymbol;

        $message = $values['name'] . ' ' . $values['surname'] . ' (' . $values['year_of_birth'] . ')';
        // send mail
        $latte = new Engine;
        $params = [
            'competition_name' => $competition->name,
            'name' => $values['name'],
            'surname' => $values['surname'],
            'sex' => ($values['sex'] === 'M') ? 'Muži' : 'Ženy',
            'birth_year' => $values['year_of_birth'],
            'category' => $category->name,
            'distance' => $distance->name,
            'team' => $values['team'],
            'price' => $price,
            'variableSymbol' => $variableSymbol,
            'message' => $message
        ];

        $mail = new Message();

        $mail->setFrom('info@hopmantriatlon.cz', 'Hopman');
        $mail->addTo($values['email']);
        $mail->setHtmlBody(
            $latte->renderToString(__DIR__ . '/../../Email/'. $competition->slug .'.latte', $params),
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

    // render startlist table
    public function createComponentStartlistGrid(): Multiplier
    {
        return new Multiplier(function ($competition_id) {

            $grid = new DataGrid();

            $competition_id = (int)$competition_id;

            // set categories
            $categories = $this->categoryModel->getForSelect();
            if ($this->competitionModel->getCompetitionById($competition_id)->slug === 'pulmaraton') {
                unset($categories[8], $categories[9]);
            } else {
                unset($categories[10], $categories[11], $categories[12], $categories[13], $categories[14]);
            }

            $grid->setDefaultSort(['surname' => 'ASC']);

            $grid->setDataSource($this->competitorModel->getCompetitors($competition_id));

            $grid->setItemsPerPageList([50, 100, 250], true);

            $grid->addColumnText('surname', 'Příjmení')
                ->setSortable();

            $grid->addColumnText('name', 'Jméno')
                ->setSortable();

            $grid->addColumnText('year_of_birth', 'Rok narození')
                ->setAlign('center')
                ->setFitContent()
                ->setSortable();

            $grid->addColumnText('team', 'Oddíl')
                ->setSortable();

            $grid->addColumnText('sex', 'Pohlaví')
                ->setSortable()
                ->setAlign('center')
                ->setFilterSelect([
                    '' => '',
                    'M' => 'M',
                    'Ž' => 'Ž'
                    ]);

            $grid->addColumnText('distance_id', 'Trať')
                ->setSortable()
                ->setReplacement($this->distanceModel->getForSelect())
                ->setFilterSelect(['' => ''] + $this->distanceModel->getDistancesByCompetition($competition_id));

            $grid->addColumnText('category_id', 'Kategorie')
                ->setSortable()
                ->setReplacement($this->categoryModel->getForSelect())
                ->setFilterSelect(['' => ''] + $categories);

            $grid->addColumnText('paid', 'Status')
                ->setSortable()
                ->setAlign('center')
                ->setReplacement([
                    0 => 'Nezaplaceno',
                    1 => 'Zaplaceno'
                ]);

            $translator = new SimpleTranslator([
                'ublaboo_datagrid.no_item_found_reset' => 'Žádné záznamy nenalezeny. Filtr můžete vynulovat',
                'ublaboo_datagrid.no_item_found' => 'Žádné záznamy nenalezeny.',
                'ublaboo_datagrid.here' => 'zde',
                'ublaboo_datagrid.items' => 'Záznamy',
                'ublaboo_datagrid.all' => 'všechny',
                'ublaboo_datagrid.from' => 'z',
                'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
                'ublaboo_datagrid.group_actions' => 'Hromadné akce',
                'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
                'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
                'ublaboo_datagrid.action' => 'Akce',
                'ublaboo_datagrid.previous' => 'Předchozí',
                'ublaboo_datagrid.next' => 'Další',
                'ublaboo_datagrid.choose' => 'Vyberte',
                'ublaboo_datagrid.execute' => 'Provést',
            ]);

            $grid->setTranslator($translator);

            return $grid;
        });
    }


    // render registration forms for relays
    protected function createComponentRelaySignUpForm(): Form
    {
        $form = new Form();

        $form->addText('competition_id', 'ID události');

        $form->addText('address', 'Ochrana proti botům');

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

                // anti-spam
                if (!empty($values['address'])) {
                    $this->flashMessage('Boti se do našeho závodu registrovat nemohou. Zkuste to znovu jako člověk.', 'warning');
                    $this->redirect('this?bot=1');
                }

                unset($values['agree']);
                unset($values['address']);
                $values['id'] = $this->relayModel->insert($values)->id;

                // get competition name
                $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
                if ($competition != NULL) {
                    $competition_name = $competition->name;
                    $event_slug = $competition->slug;

                    // payment
                    $price = 600;

                    $variableSymbol = str_pad((string)$values['id'], 5, "0", STR_PAD_LEFT);
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
                $this->flashMessage('Při pokusu o registraci nastala chyba a záznam nebyl uložen. Nejspíš je to naše chyba. Kontaktujte prosím správce webu na info@hopmantriatlon.cz a my se to pokusíme co nejrychleji opravit :-)', 'danger');
            }
        };

        return $form;
    }

}
