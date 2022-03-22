<?php

namespace App\AdminModule\Presenters;

use App\Model\CategoryModel;
use App\Model\CompetitionModel;
use App\Model\CompetitorModel;
use App\Model\DistanceModel;
use App\Model\EventModel;
use Dibi\Connection;
use K2D\Core\AdminModule\Presenter\BasePresenter;
use Nette\Application\UI\Multiplier;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

class EventPresenter extends BasePresenter
{
    /** @inject */
    public Connection $dibi;

    /** @inject */
	public EventModel $eventModel;

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
                    $this->flashMessage(sprintf('Id: %s, new value: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['surname' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('name', 'Jméno')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Id: %s, new value: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['name' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('year_of_birth', 'Rok narození')
                ->setAlign('center')
                ->setFitContent()
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Id: %s, new value: %s', $id, $value));
                    $competitior = $this->competitorModel->getCompetitor($id);
                    $competitior->update(['year_of_birth' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('team', 'Oddíl')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Id: %s, new value: %s', $id, $value));
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

    public function updatePaymentStatus($id, $status)
    {
        $competitor = $this->competitorModel->getCompetitor($id);
        $competitor->update(['paid' => $status]);

        $status_text = ['nezaplaceno', 'zaplaceno'][$status];

        $this->flashMessage("Status řádku $id byl změněm na $status_text.", "success");

        $this->redrawControl('flashes');
        $this->redirect('this');
    }
}
