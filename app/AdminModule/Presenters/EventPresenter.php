<?php

namespace App\AdminModule\Presenters;

use App\Model\CategoryModel;
use App\Model\CompetitionModel;
use App\Model\CompetitorModel;
use App\Model\DistanceModel;
use App\Model\EventModel;
use App\Model\StatusModel;
use K2D\Core\AdminModule\Presenter\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

class EventPresenter extends BasePresenter
{
    /** @inject */
    public EventModel $eventModel;

    /** @inject */
    public StatusModel $statusModel;

    /** @inject */
    public CompetitorModel $competitorModel;

    /** @inject */
    public CompetitionModel $competitionModel;

    /** @inject DistanceModel */
    public DistanceModel $distanceModel;

    /** @inject DistanceModel */
    public CategoryModel $categoryModel;

    public function renderDefault(): void
    {
        $this->template->events = $this->eventModel->getActiveEvents();
        $currYear = date('Y');
        $this->template->competitions = $this->competitionModel->getThisYearsCompetitions($currYear);
    }

    public function renderEdit(string $slug): void
    {
        $this->template->event = $this->eventModel->getEvent($slug);
    }

    public function renderRegistered(int $competition_id): void
    {
        $this->template->competition = $this->competitionModel->getCompetitionById($competition_id);
    }

    // render startlist page
    public function renderStartlist(int $competition_id): void
    {
        $this->template->competition = $this->competitionModel->getCompetitionById($competition_id);
    }

    // render startlist table
    public function createComponentAdminStartlistGrid(): Multiplier
    {
        return new Multiplier(function ($competition_id) {

            $grid = new DataGrid();

            $competition_id = (int)$competition_id;

            $grid->setDefaultSort('id');

            $grid->setDataSource($this->competitorModel->getCompetitors($competition_id));

            $grid->setItemsPerPageList([25, 50, 100, 250], true);

            $grid->addColumnText('id', 'ID')
                ->setSortable();

            $grid->addColumnText('surname', 'Příjmení')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nové příjmení: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['surname' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('name', 'Jméno')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nové jméno: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['name' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('year_of_birth', 'Rok narození')
                ->setAlign('center')
                ->setFitContent()
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nový rok narození: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['year_of_birth' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('team', 'Oddíl')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nový název města/oddílu: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['team' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('sex', 'Pohlaví')
                ->setAlign('center')
                ->setFitContent();

            $grid->addColumnText('distance_id', 'Trať')
                ->setReplacement($this->distanceModel->getForSelect())
                ->setFilterSelect(['' => ''] + $this->distanceModel->getDistancesByCompetition($competition_id));

            $grid->addColumnText('category_id', 'Kategorie')
                ->setSortable()
                ->setReplacement($this->categoryModel->getForSelect())
                ->setFilterSelect(['' => ''] + $this->categoryModel->getForSelect());

            $grid->addColumnText('email', 'Email')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Id: %s, new value: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['email' => $value]);
                    $this->redrawControl('flashes');
                });


            $grid->addColumnStatus('paid', 'Status')
                ->setSortable()
                ->setCaret(true)
                ->addOption(1, 'Zaplaceno')
                ->setIcon('check')
                ->setClass('btn-success')
                ->endOption()
                ->addOption(0, 'Nezaplaceno')
                ->setIcon('close')
                ->setClass('btn-danger')
                ->endOption()
                ->onChange[] = [$this, 'updatePaymentStatus'];

            $grid->addExportCsv('Csv export', 'startovka-all.csv')
                ->setTitle('Export CSV');

			$grid->addGroupAction('Smazat')->onSelect[] = [$this, 'groupDelete'];

			$grid->addGroupAction('Změnit status platby')->onSelect[] = [$this, 'groupPaid'];

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

    public function createComponentEventForm(): Multiplier
    {
        return new Multiplier(function ($event_id) {

            $event_id = (int)$event_id;
            $form = new Form();

            $form->setHtmlAttribute('class', 'ajax');

            $form->addSelect('competition_id', 'Aktuální ročník')
                ->setItems($this->competitionModel->getForSelectById($event_id));

            $form->addSelect('status_id', 'Status závodu')
                ->setItems($this->statusModel->getForSelect('id', 'value'));

            $form->addCheckbox('propositions_active', 'Propozice');

            $form->addCheckbox('registration_active', 'Registrace');

            $form->addCheckbox('startlist_active', 'Startovní listina');

            $form->addSubmit('save', 'Uložit změny');

            $form->setDefaults($this->eventModel->getEventById($event_id));

            $form->onSubmit[] = function (Form $form) use ($event_id) {
                $values = $form->getValues(true);
                if($this->eventModel->getTable()->where('id', $event_id)->update($values)) {
                    $this->flashMessage('Změny byly uloženy!', 'success');
                } else {
                    $this->flashMessage('K žádným změnám nedošlo!', 'warning');
                }
            };

            return $form;
        });
    }

    public function updatePaymentStatus($id, $status)
    {
        $competitor = $this->competitorModel->getCompetitor($id);
        $competitor->update(['paid' => $status]);

        $status_text = ['nezaplaceno', 'zaplaceno'][$status];

        $this->flashMessage("Status řádku $id byl změněn na $status_text.", "success");

        if ($this->isAjax()) {
            $this['adminStartlistGrid-' . $competitor->competition_id]->reload();
        } else {
            $this->redirect('this');
        }
    }

	public function groupDelete($id, $status)
	{

		$competitor = $this->competitorModel->getCompetitor($id[0]);

		if ($this->competitorModel->getTable()->where('id', $id)->delete()) {
			$this->flashMessage("Vybrané záznamy byly úspěšně odstraněny.", "success");
		} else {
			$this->flashMessage("Vybrané záznamy se nepodařilo odstranit odstraněny.", "warning");
		}

		if ($this->isAjax()) {
			$this->redrawControl('flashes');
			$this['adminStartlistGrid-' . $competitor->competition_id]->reload();
		} else {
			$this->redirect('this');
		}
	}


	public function groupPaid($id, $status)
	{
		$competitor = $this->competitorModel->getCompetitor($id[0]);
		foreach ($id as $i) {
			$competitor = $this->competitorModel->getCompetitor($i);
			if ($competitor->paid)
				$status = 0;
			else
				$status = 1;

			$competitor->update(['paid' => $status]);
			$status_text = ['nezaplaceno', 'zaplaceno'][$status];
			$this->flashMessage("Status platby u řádku ID $competitor->id byl změněn na $status_text.", "success");
		}

		if ($this->isAjax()) {
			$this->redrawControl('flashes');
			$this['adminStartlistGrid-' . $competitor->competition_id]->reload();
		} else {
			$this->redirect('this');
		}
	}
}
