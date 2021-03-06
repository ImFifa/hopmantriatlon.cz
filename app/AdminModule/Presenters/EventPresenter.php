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

            $grid->addColumnText('surname', 'P????jmen??')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Zm??na u ????dku ID: %s, Nov?? p????jmen??: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['surname' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('name', 'Jm??no')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Zm??na u ????dku ID: %s, Nov?? jm??no: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['name' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('year_of_birth', 'Rok narozen??')
                ->setAlign('center')
                ->setFitContent()
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Zm??na u ????dku ID: %s, Nov?? rok narozen??: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['year_of_birth' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('team', 'Odd??l')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Zm??na u ????dku ID: %s, Nov?? n??zev m??sta/odd??lu: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['team' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('sex', 'Pohlav??')
                ->setAlign('center')
                ->setFitContent();

            $grid->addColumnText('distance_id', 'Tra??')
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

			$grid->addGroupAction('Zm??nit status platby')->onSelect[] = [$this, 'groupPaid'];

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

    public function createComponentEventForm(): Multiplier
    {
        return new Multiplier(function ($event_id) {

            $event_id = (int)$event_id;
            $form = new Form();

            $form->setHtmlAttribute('class', 'ajax');

            $form->addSelect('competition_id', 'Aktu??ln?? ro??n??k')
                ->setItems($this->competitionModel->getForSelectById($event_id));

            $form->addSelect('status_id', 'Status z??vodu')
                ->setItems($this->statusModel->getForSelect('id', 'value'));

            $form->addCheckbox('propositions_active', 'Propozice');

            $form->addCheckbox('registration_active', 'Registrace');

            $form->addCheckbox('startlist_active', 'Startovn?? listina');

            $form->addSubmit('save', 'Ulo??it zm??ny');

            $form->setDefaults($this->eventModel->getEventById($event_id));

            $form->onSubmit[] = function (Form $form) use ($event_id) {
                $values = $form->getValues(true);
                if($this->eventModel->getTable()->where('id', $event_id)->update($values)) {
                    $this->flashMessage('Zm??ny byly ulo??eny!', 'success');
                } else {
                    $this->flashMessage('K ????dn??m zm??n??m nedo??lo!', 'warning');
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

        $this->flashMessage("Status ????dku $id byl zm??n??n na $status_text.", "success");

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
			$this->flashMessage("Vybran?? z??znamy byly ??sp????n?? odstran??ny.", "success");
		} else {
			$this->flashMessage("Vybran?? z??znamy se nepoda??ilo odstranit odstran??ny.", "warning");
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
			$this->flashMessage("Status platby u ????dku ID $competitor->id byl zm??n??n na $status_text.", "success");
		}

		if ($this->isAjax()) {
			$this->redrawControl('flashes');
			$this['adminStartlistGrid-' . $competitor->competition_id]->reload();
		} else {
			$this->redirect('this');
		}
	}
}
