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
                    $this->template->kids = $this->competitorModel->getRegisteredKids($event->competition_id + 2);
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
        $slug = explode('-', $slug);
        $event = $this->eventModel->getEvent($slug[0]);

        if (sizeof($slug) > 1) {
            if ($slug[1] === 'deti') {
                $competition = $this->competitionModel->getCompetitionById(9);
            } else {
                $competition = $this->competitionModel->getCompetitionById(8);
            }
        } else {
            $competition = $this->competitionModel->getCompetitionById(7);
        }

        if (!$competition) {
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
            $this->template->competition = $competition;
            $this->template->competitions = $this->competitionModel->getThisYearsActiveCompetitionsById($event->id, $currYear);
        }
    }


    // render registration confirmation page
    public function renderRegistrationSent($slug): void
    {

        $competition = $this->competitionModel->getSelectedCompetition($slug);
        $slug = explode('-', $slug)[0];
        $event = $this->eventModel->getEvent($slug);

        if (!$competition) {
            $this->error();
        } else {
            $this->template->event = $event;
            $this->template->competition = $competition;
        }
    }

    // render startlist page (without startlist table)
    public function renderStartlist($slug): void
    {
        $event = $this->eventModel->getEvent($slug);
        if (!$event) {
            $this->error();
        } else {
            $competition = $this->competitionModel->getCompetitionById($event->competition_id);
            $this->template->competition = $competition;
            $this->template->event = $event;
        }
    }

    // render registration forms for individual competitions
    protected function createComponentSignUpForm(): Multiplier
    {
        return new Multiplier(function ($competition_id) {

            $competition_id = (int)$competition_id;
            $competition = $this->competitionModel->getCompetitionById($competition_id);
            $currentYear = date("Y");
            $form = new Form();

            $form->addText('competition_id', 'ID ud??losti');
            $form->addText('address', 'Ochrana proti bot??m');

            $form->addText('name', 'Jm??no')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 100)
                ->setRequired('Mus??te uv??st Va??e jm??no');

            $form->addText('surname', 'P????jmen??')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 100)
                ->setRequired('Mus??te uv??st Va??e p????jmen??');

            $form->addSelect('distance_id', 'Tra??')
                ->setPrompt('-------')
                ->setRequired('Mus??te si zvolit tra??')
                ->setItems([
                    12 => 'Sprint triatlon',
                    11 => 'Olympijsk?? triatlon'
                ]);


            $form->addInteger('year_of_birth', 'Rok narozen??')
                ->addRule(Form::LENGTH, 'Po??adovan?? d??lka jsou %s znaky', 4)
                ->addRule(Form::MAX, 'Pro start v hlavn??m z??vod?? ti mus?? b??t alespo?? 15 let', $currentYear - 15)
                ->addRule(Form::MIN, 'V????n?? je V??m v??c ne?? 100 let? :-)', $currentYear - 100)
                ->setRequired('Mus??te uv??st V???? rok narozen??');

            $form->addSelect('sex', 'Pohlav??')
                ->setPrompt('-------')
                ->setRequired('Mus??te uv??st Va??e pohlav??')
                ->setItems([
                    'M' => 'Mu??',
                    '??' => '??ena'
                ]);

            $form->addText('team', 'Odd??l/m??sto')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 150);

            $form->addEmail('email', 'Emailov?? adresa')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 200)
                ->setRequired('Mus??te uv??st Va??i emailovou adresu');

            $form->addSelect('size', 'Tri??ko')
                ->setPrompt('-------')
                ->setRequired('Mus??te uv??st, jestli chcete ????astnick?? tri??ko.')
                ->setItems([
                    'Ne' => 'Nechci tri??ko',
                    'XS' => 'XS (+200K??)',
                    'S' => 'S (+200K??)',
                    'M' => 'M (+200K??)',
                    'L' => 'L (+200K??)',
                    'XL' => 'XL (+200K??)',
                    'XXL' => 'XXL (+200K??)'
                ]);

            $form->addCheckbox('agree', 'Souhlas??m s vyu??it??m osobn??ch ??daj?? za ????elem zpracov??n?? v??sledk?? z??vodu.')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired('Je pot??eba souhlasit s podm??nkami');

            $form->addInvisibleReCaptcha('recaptcha')
                ->setMessage('Jste opravdu ??lov??k?');

            $form->addSubmit('submit', 'Odeslat p??ihl????ku');

            $form->addButton('reset')
                ->setHtmlAttribute('type', 'reset')
                ->setCaption('Restartovat formul????');

            $form->onSubmit[] = function (Form $form) {
                try {
                    $currentYear = date("Y");
                    $values = $form->getValues(true);

                    // set category
                    if ($currentYear - $values['year_of_birth'] < 18) {
                        if ($values['sex'] === 'M')
                            $values['category_id'] = 4;
                        else
                            $values['category_id'] = 5;
                    } elseif ($currentYear - $values['year_of_birth'] < 40) {
                        if ($values['sex'] === 'M')
                            $values['category_id'] = 6;
                        else
                            $values['category_id'] = 7;
                    } else {
                        if ($values['sex'] === 'M')
                            $values['category_id'] = 8;
                        else
                            $values['category_id'] = 9;
                    }

                    // anti-spam
                    if (!empty($values['address'])) {
                        $this->flashMessage('Boti se do na??eho z??vodu registrovat nemohou. Zkuste to znovu jako ??lov??k.', 'warning');
                        $this->redirect('this?bot=1');
                    }

                    // chybny vek
                    $distance = $this->distanceModel->getDistanceById($values['distance_id']);
                    if ($distance->min_age != NULL && $distance->min_age > ($currentYear - $values['year_of_birth'])) {
                        throw new \InvalidArgumentException($values['name'] . ' ' . $values['surname'] . ' se pokusil p??ihl??sit na tra?? '.$distance->name.' jako ro??n??k '.$values['year_of_birth'].'.');
                    }

                    unset($values['reset']);
                    unset($values['agree']);
                    unset($values['address']);

                    // insert to database
                    $values['id'] = $this->competitorModel->insert($values)->id;

                    // send confirmation mail
                    $this->sendConfirmationMail($values);

                    // get competition name
                    $competition = $this->competitionModel->getCompetitionById($values['competition_id']);

                    $this->flashMessage('Jsi zaregistrov??n/a na z??vod '.$competition->name.'! Na adresu '. $values['email'] .' ti byl pr??v?? odesl??n potvrzovac?? email s vypln??n??mi ??daji a informacemi k platb??.');
                    $this->logModel->log('??sp????n?? registrace', $competition->name . ' - ' . $values['name'] . ' ' . $values['surname'] . ' se pr??v?? zaregistroval.', 'info');

                    $this->redirect('Event:registrationSent', $competition->event->slug);

                } catch (SmtpException $e) {
					$competition = $this->competitionModel->getCompetitionById($values['competition_id']);
                    $this->flashMessage('Registrace se nezda??ila, proto??e nebylo mo??n?? odeslat potvrzovac?? email. Zadali jste spr??vnou adresu? Pokud ano, kontaktujte pros??m spr??vce webu na info@hopmantriatlon.cz.', 'danger');
                    $this->logModel->log('Nezda??en?? registrace', $competition->name . ' - ' . $e->getMessage(), 'error');
                } catch (\InvalidArgumentException $e) {
                    $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
                    $this->flashMessage('V tomto v??ku nelze startovat na zvolen?? trati.', 'danger');
                    $this->logModel->log('Nezda??en?? registrace', $competition->name . ' - ' . $e->getMessage(), 'error');
                }
            };

            return $form;
        });
    }

    // render registration forms for individual competitions
    protected function createComponentChildrenSignUpForm(): Multiplier
    {
        return new Multiplier(function ($competition_id) {

            $currentYear = date("Y");
            $form = new Form();

            $form->addText('competition_id', 'ID ud??losti');
            $form->addText('address', 'Ochrana proti bot??m');

            $form->addText('name', 'Jm??no')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 100)
                ->setRequired('Mus??te uv??st Va??e jm??no');

            $form->addText('surname', 'P????jmen??')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 100)
                ->setRequired('Mus??te uv??st Va??e p????jmen??');

            $form->addInteger('year_of_birth', 'Rok narozen??')
                ->addRule(Form::LENGTH, 'Po??adovan?? d??lka jsou %s znaky', 4)
                ->addRule(Form::MIN, 'Na d??tsk?? triatlon se mohou p??ihl??sit pouze d??ti do 10 let.', $currentYear - 10)
                ->setRequired('Mus??te uv??st V???? rok narozen??');

            $form->addSelect('sex', 'Pohlav??')
                ->setPrompt('-------')
                ->setRequired('Mus??te uv??st Va??e pohlav??')
                ->setItems([
                    'M' => 'Mu??',
                    '??' => '??ena'
                ]);

            $form->addText('team', 'Odd??l/m??sto')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 150);

            $form->addEmail('email', 'Emailov?? adresa')
                ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 200)
                ->setRequired('Mus??te uv??st Va??i emailovou adresu');

            $form->addSelect('size', 'Tri??ko')
                ->setPrompt('-------')
                ->setRequired('Mus??te uv??st velikost ????astnick??ho tri??ka.')
                ->setItems([
                    '4' => '110cm / 4 roky',
                    '6' => '122cm / 6 let',
                    '8' => '134cm / 8 let',
                    '10' => '146cm / 10 let',
                    '12' => '158cm / 12 let',
                ]);

            $form->addCheckbox('agree', 'Souhlas??m s vyu??it??m osobn??ch ??daj?? za ????elem zpracov??n?? v??sledk?? z??vodu.')
                ->setHtmlAttribute('class', 'form-control')
                ->setRequired('Je pot??eba souhlasit s podm??nkami');

            $form->addInvisibleReCaptcha('recaptcha')
                ->setMessage('Jste opravdu ??lov??k?');

            $form->addSubmit('submit', 'Odeslat p??ihl????ku');

            $form->addButton('reset')
                ->setHtmlAttribute('type', 'reset')
                ->setCaption('Restartovat formul????');

            $form->onSubmit[] = function (Form $form) {
                try {
                    $currentYear = date("Y");
                    $values = $form->getValues(true);

                    $values['distance_id'] = 13;
                    $values['category_id'] = 1;

                    // anti-spam
                    if (!empty($values['address'])) {
                        $this->flashMessage('Boti se do na??eho z??vodu registrovat nemohou. Zkuste to znovu jako ??lov??k.', 'warning');
                        $this->redirect('this?bot=1');
                    }

                    unset($values['reset']);
                    unset($values['agree']);
                    unset($values['address']);

                    // insert to database
                    $values['id'] = $this->competitorModel->insert($values)->id;

                    // send confirmation mail
                    $this->sendConfirmationMail($values);

                    // get competition name
                    $competition = $this->competitionModel->getCompetitionById($values['competition_id']);

                    $this->flashMessage('Jsi zaregistrov??n/a na z??vod '.$competition->name.'! Na adresu '. $values['email'] .' ti byl pr??v?? odesl??n potvrzovac?? email s vypln??n??mi ??daji a informacemi k platb??.');
                    $this->logModel->log('??sp????n?? registrace', $competition->name . ' - ' . $values['name'] . ' ' . $values['surname'] . ' se pr??v?? zaregistroval.', 'info');

                    $this->redirect('Event:registrationSent', $competition->event->slug);

                } catch (SmtpException $e) {
                    $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
                    $this->flashMessage('Registrace se nezda??ila, proto??e nebylo mo??n?? odeslat potvrzovac?? email. Zadali jste spr??vnou adresu? Pokud ano, kontaktujte pros??m spr??vce webu na info@hopmantriatlon.cz.', 'danger');
                    $this->logModel->log('Nezda??en?? registrace', $competition->name . ' - ' . $e->getMessage(), 'error');
                } catch (\InvalidArgumentException $e) {
                    $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
                    $this->flashMessage('V tomto v??ku nelze startovat na zvolen?? trati.', 'danger');
                    $this->logModel->log('Nezda??en?? registrace', $competition->name . ' - ' . $e->getMessage(), 'error');
                }
            };

            return $form;
        });
    }

    private function sendConfirmationMail(array $values): void
    {
        bdump($values);
        $category = $this->categoryModel->getCategoryById($values['category_id']);
        $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
        $distance = $this->distanceModel->getDistanceById($values['distance_id']);

        // setup starting fee
        $price = $distance->price;
        if (($category->name === 'Dorostenci' || $category->name === 'Dorostenky') && $competition->event_id != 3) {
            $price -= 50;
        }

        if ($competition->event_id === 3 && $values['category_id'] != 1 && $values['size'] != 'Ne') {
            $price += 200;
        }

        $variableSymbol = str_pad((string)$values['id'], 3, "0", STR_PAD_LEFT);
        $variableSymbol = 101 . $variableSymbol;

        $message = $values['name'] . ' ' . $values['surname'] . ' (' . $values['year_of_birth'] . ')';
        // send mail
        $latte = new Engine;
        $params = [
            'competition_name' => $competition->name,
            'name' => $values['name'],
            'surname' => $values['surname'],
            'sex' => ($values['sex'] === 'M') ? 'Mu??i' : '??eny',
            'birth_year' => $values['year_of_birth'],
            'category' => $category->name,
            'distance' => $distance->name,
            'team' => $values['team'],
            'price' => $price,
            'variableSymbol' => $variableSymbol,
            'message' => $message
        ];

        if ($competition->event_id === 3) {
            $params = $params + [
                'size' => ($values['size'] === 'Ne') ? 'Nechci tri??ko' : 'Velikost ' . $values['size']
                ];
        }

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
//            if ($this->competitionModel->getCompetitionById($competition_id)->slug === 'pulmaraton') {
//                unset($categories[8], $categories[9]);
//            } else {
//                unset($categories[10], $categories[11], $categories[12], $categories[13], $categories[14]);
//            }
            if ($this->competitionModel->getCompetitionById($competition_id)->slug === 'triatlon') {
                unset($categories[1], $categories[2],$categories[3],
                    $categories[10], $categories[11],
                    $categories[12], $categories[13], $categories[14]);
            }

            $grid->setDefaultSort(['surname' => 'ASC']);

            $grid->setDataSource($this->competitorModel->getCompetitors($competition_id));

            $grid->setItemsPerPageList([50, 100, 250], true);

            $grid->addColumnText('surname', 'P????jmen??')
                ->setSortable();

            $grid->addColumnText('name', 'Jm??no')
                ->setSortable();

            $grid->addColumnText('year_of_birth', 'Rok narozen??')
                ->setAlign('center')
                ->setFitContent()
                ->setSortable();

            $grid->addColumnText('team', 'Odd??l')
                ->setSortable();

            $grid->addColumnText('sex', 'Pohlav??')
                ->setSortable()
                ->setAlign('center')
                ->setFilterSelect([
                    '' => '',
                    'M' => 'M',
                    '??' => '??'
                ]);

            $grid->addColumnText('distance_id', 'Tra??')
                ->setSortable()
                ->setReplacement($this->distanceModel->getForSelect())
                ->setFilterSelect([
                    '' => '',
                    11 => 'Olympijsk?? triatlon',
                    12 => 'Sprint triatlon'
                ]);

            $grid->addColumnText('category_id', 'Kategorie')
                ->setSortable()
                ->setReplacement($this->categoryModel->getForSelect())
                ->setFilterSelect(['' => ''] + $categories);

            $grid->addColumnText('paid', 'Status')
                ->setSortable()
                ->setReplacement([
                    0 => 'Nezaplaceno',
                    1 => 'Zaplaceno'
                ]);

            $translator = new SimpleTranslator([
                'ublaboo_datagrid.no_item_found_reset' => '????dn?? z??znamy nenalezeny. Filtr m????ete vynulovat',
                'ublaboo_datagrid.no_item_found' => '????dn?? z??znamy nenalezeny.',
                'ublaboo_datagrid.here' => 'zde',
                'ublaboo_datagrid.items' => 'Z??znamy',
                'ublaboo_datagrid.all' => 'v??echny',
                'ublaboo_datagrid.from' => 'z',
                'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
                'ublaboo_datagrid.group_actions' => 'Hromadn?? akce',
                'ublaboo_datagrid.show_all_columns' => 'Zobrazit v??echny sloupce',
                'ublaboo_datagrid.hide_column' => 'Skr??t sloupec',
                'ublaboo_datagrid.action' => 'Akce',
                'ublaboo_datagrid.previous' => 'P??edchoz??',
                'ublaboo_datagrid.next' => 'Dal????',
                'ublaboo_datagrid.choose' => 'Vyberte',
                'ublaboo_datagrid.execute' => 'Prov??st',
            ]);

            $grid->setTranslator($translator);

            return $grid;
        });
    }


    // render registration forms for relays
    protected function createComponentTriathlonRelaySignUpForm(): Form
    {
        $form = new Form();

        $form->addText('competition_id', 'ID ud??losti');

        $form->addText('address', 'Ochrana proti bot??m');

        $form->addText('name', 'N??zev ??tafety')
            ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 160)
            ->setRequired('??tafeta mus?? m??t n??jak?? n??zev');

        $form->addText('competitor1', 'Z??vodn??k 1')
            ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 160)
            ->setRequired('Mus??te uv??st jm??no 1. z??vodn??ka');

        $form->addSelect('sex1', 'Pohlav??')
            ->setPrompt('-------')
            ->setRequired('Mus??te uv??st pohlav?? 1. z??vodn??ka')
            ->setItems([
                'M' => 'Mu??',
                '??' => '??ena'
            ]);

        $form->addSelect('shirt1', 'Tri??ko')
            ->setPrompt('-------')
            ->setRequired('Mus??te uv??st, jestli chcete tri??ko pro 1. z??vodn??ka')
            ->setItems([
                'Ne' => 'Nechci tri??ko',
                'XS' => 'XS (+200K??)',
                'S' => 'S (+200K??)',
                'M' => 'M (+200K??)',
                'L' => 'L (+200K??)',
                'XL' => 'XL (+200K??)',
                'XXL' => 'XXL (+200K??)'
            ]);

        $form->addText('competitor2', 'Z??vodn??k 2')
            ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 160)
            ->setRequired('Mus??te uv??st jm??no 2. z??vodn??ka');

        $form->addSelect('sex2', 'Pohlav??')
            ->setPrompt('-------')
            ->setRequired('Mus??te uv??st pohlav?? 2. z??vodn??ka')
            ->setItems([
                'M' => 'Mu??',
                '??' => '??ena'
            ]);

        $form->addSelect('shirt2', 'Tri??ko')
            ->setPrompt('-------')
            ->setRequired('Mus??te uv??st, jestli chcete tri??ko pro 2. z??vodn??ka')
            ->setItems([
                'Ne' => 'Nechci tri??ko',
                'XS' => 'XS (+200K??)',
                'S' => 'S (+200K??)',
                'M' => 'M (+200K??)',
                'L' => 'L (+200K??)',
                'XL' => 'XL (+200K??)',
                'XXL' => 'XXL (+200K??)'
            ]);

        $form->addText('competitor3', 'Z??vodn??k 3')
            ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 160)
            ->setRequired('Mus??te uv??st jm??no 3. z??vodn??ka');

        $form->addSelect('sex3', 'Pohlav??')
            ->setPrompt('-------')
            ->setRequired('Mus??te uv??st pohlav?? 3. z??vodn??ka')
            ->setItems([
                'M' => 'Mu??',
                '??' => '??ena'
            ]);

        $form->addSelect('shirt3', 'Tri??ko')
            ->setPrompt('-------')
            ->setRequired('Mus??te uv??st, jestli chcete tri??ko pro 2. z??vodn??ka')
            ->setItems([
                'Ne' => 'Nechci tri??ko',
                'XS' => 'XS (+200K??)',
                'S' => 'S (+200K??)',
                'M' => 'M (+200K??)',
                'L' => 'L (+200K??)',
                'XL' => 'XL (+200K??)',
                'XXL' => 'XXL (+200K??)'
            ]);

        $form->addEmail('email', 'Kontaktn?? emailov?? adresa')
            ->addRule(Form::MAX_LENGTH, 'Maxim??ln?? d??lka je %s znak??', 200)
            ->setRequired('Mus??te uv??st kontaktn?? emailovou adresu');

        $form->addCheckbox('agree', 'Souhlas??m s podm??nkami z??vodu a vyu??it??m osobn??ch ??daj?? za ????elem zpracov??n?? v??sledk?? z??vodu.')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Je pot??eba souhlasit s podm??nkami');

        $form->addInvisibleReCaptcha('recaptcha')
            ->setMessage('Jste opravdu ??lov??k?');

        $form->addSubmit('submit', 'Odeslat p??ihl????ku');

        $form->onSubmit[] = function (Form $form) {
            try {
                $values = $form->getValues(true);

                // anti-spam
                if (!empty($values['address'])) {
                    $this->flashMessage('Boti se do na??eho z??vodu registrovat nemohou. Zkuste to znovu jako ??lov??k.', 'warning');
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
                    $price = 750;

                    if ($values['shirt1'] != 'Ne') {
                        $price += 200;
                    }

                    if ($values['shirt2'] != 'Ne') {
                        $price += 200;
                    }

                    if ($values['shirt3'] != 'Ne') {
                        $price += 200;
                    }

                    $variableSymbol = str_pad((string)$values['id'], 3, "0", STR_PAD_LEFT);
                    $variableSymbol = 201 . $variableSymbol;

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
                        'message' => 'Hopman triatlon - ??tafeta ' . $values['name'],
                        'shirt1' => ($values['shirt1'] === 'Ne') ? 'Nechci tri??ko' : 'Velikost ' . $values['shirt1'],
                        'shirt2' => ($values['shirt2'] === 'Ne') ? 'Nechci tri??ko' : 'Velikost ' . $values['shirt2'],
                        'shirt3' => ($values['shirt3'] === 'Ne') ? 'Nechci tri??ko' : 'Velikost ' . $values['shirt3'],
                        'sex1' => $values['sex1'],
                        'sex2' => $values['sex2'],
                        'sex3' => $values['sex3'],
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


                // get competition name
                $competition = $this->competitionModel->getCompetitionById($values['competition_id']);

                $this->flashMessage('Jsi zaregistrov??n/a na z??vod '.$competition->name.'! Na adresu '. $values['email'] .' ti byl pr??v?? odesl??n potvrzovac?? email s vypln??n??mi ??daji a informacemi k platb??.');
                $this->logModel->log('??sp????n?? registrace', $competition->name . ' - ??tafeta ' . $values['name'] . ' ' . ' se pr??v?? zaregistrovala.', 'info');

                $this->redirect('Event:registrationSent', $competition->event->slug);

            } catch (SmtpException $e) {
                $competition = $this->competitionModel->getCompetitionById($values['competition_id']);
                $this->flashMessage('Registrace se nezda??ila, proto??e nebylo mo??n?? odeslat potvrzovac?? email. Zadali jste spr??vnou adresu? Pokud ano, kontaktujte pros??m spr??vce webu na info@hopmantriatlon.cz.', 'danger');
                $this->logModel->log('Nezda??en?? registrace', $competition->name . ' - ' . $e->getMessage(), 'error');
            }
        };

        return $form;
    }

    // render startlist table
    public function createComponentRelayStartlistGrid(): Multiplier
    {
        return new Multiplier(function ($competition_id) {

            $grid = new DataGrid();

            $competition_id = (int)$competition_id;

            $grid->setDefaultSort(['name' => 'ASC']);

            $grid->setDataSource($this->relayModel->getRelays($competition_id));

            $grid->setItemsPerPageList([50, 100, 250], true);

            $grid->addColumnText('name', 'N??zev ??tafety')
                ->setSortable();

            $grid->addColumnText('competitor1', 'Z??vodn??k 1')
                ->setSortable();

            $grid->addColumnText('competitor2', 'Z??vodn??k 2')
                ->setSortable();

            $grid->addColumnText('competitor3', 'Z??vodn??k 3')
                ->setSortable();

            $grid->addColumnText('paid', 'Status')
                ->setSortable()
                ->setReplacement([
                    0 => 'Nezaplaceno',
                    1 => 'Zaplaceno'
                ]);

            $translator = new SimpleTranslator([
                'ublaboo_datagrid.no_item_found_reset' => '????dn?? z??znamy nenalezeny. Filtr m????ete vynulovat',
                'ublaboo_datagrid.no_item_found' => '????dn?? z??znamy nenalezeny.',
                'ublaboo_datagrid.here' => 'zde',
                'ublaboo_datagrid.items' => 'Z??znamy',
                'ublaboo_datagrid.all' => 'v??echny',
                'ublaboo_datagrid.from' => 'z',
                'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
                'ublaboo_datagrid.group_actions' => 'Hromadn?? akce',
                'ublaboo_datagrid.show_all_columns' => 'Zobrazit v??echny sloupce',
                'ublaboo_datagrid.hide_column' => 'Skr??t sloupec',
                'ublaboo_datagrid.action' => 'Akce',
                'ublaboo_datagrid.previous' => 'P??edchoz??',
                'ublaboo_datagrid.next' => 'Dal????',
                'ublaboo_datagrid.choose' => 'Vyberte',
                'ublaboo_datagrid.execute' => 'Prov??st',
            ]);

            $grid->setTranslator($translator);

            return $grid;
        });
    }
}
