<?php

namespace App\AdminModule\Presenters;

use App\Model\CompetitionModel;
use App\Model\CompetitorModel;
use App\Model\EventModel;
use K2D\Core\AdminModule\Presenter\BasePresenter;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\ColumnText;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

class EventPresenter extends BasePresenter
{
	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public CompetitorModel $competitorModel;

	/** @inject */
	public CompetitionModel $competitionModel;

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
		bdump($competition_id);
		bdump($this->competitorModel->getCompetitors($competition_id));

	}

	public function createComponentGrid($id): DataGrid
	{
		$grid = new DataGrid;

		$grid->setDataSource($this->competitorModel->getCompetitors($id));

		$grid->setItemsPerPageList([30, 50, 100]);

		$grid->addColumnNumber('id', 'ID')
			->setSortable()
			->setAlign('center');

		// prijmeni
		$grid->addColumnText('surname', 'Příjmení')
			->setSortable()
			->setFilterText();

		// jmeno
		$grid->addColumnText('name', 'Jméno')
			->setSortable()
			->setFilterText();

		// email
		$grid->addColumnText('email', 'Email')
			->setSortable()
			->setFilterText();

		$inlineEdit = $grid->addInlineEdit();

		$inlineEdit->onControlAdd[] = function($container) {
			$container->addText('name', '')
				->setRequired('aaa');
		};

		$inlineEdit->onSetDefaults[] = function(Container $container, Row $row) {
			$container->setDefaults([
				'id' => $row['id'],
				'name' => $row['name'],
			]);
		};

		$inlineEdit->onSubmit[] = function($id, $values) {
			$this->flashMessage('Record was updated! (not really)', 'success');
			$this->redrawControl('flashes');
		};

		$inlineEdit->setShowNonEditingColumns();

		$columnSurname = new ColumnText($grid, 'surname', 'surname', 'Příjmení');
		$columnName = new ColumnText($grid, 'name', 'name', 'Jméno');

		$grid->addExportCsv('Csv export', 'startlist-2021.csv')
			->setTitle('Csv export')
			->setColumns([
				$columnName,
				$columnSurname,
			]);

		// translate
		$translator = new SimpleTranslator([
			'ublaboo_datagrid.no_item_found_reset' => 'Žádné položky nenalezeny. Filtr můžete vynulovat',
			'ublaboo_datagrid.no_item_found' => 'Žádné položky nenalezeny.',
			'ublaboo_datagrid.here' => 'zde',
			'ublaboo_datagrid.items' => 'Položky',
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
	}


}
