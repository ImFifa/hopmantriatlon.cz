<?php declare(strict_types=1);

namespace App\AdminModule\Grid;

use App\Model\CustomFileModel;
use App\Model\CustomNewModel;
use K2D\Core\AdminModule\Grid\BaseV2Grid;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Container;

class CustomNewGrid extends BaseV2Grid
{

	private CustomNewModel $customNewModel;

	private CustomFileModel $customFileModel;

	public function __construct(CustomNewModel $customNewModel, CustomFileModel $customFileModel)
	{
		parent::__construct();
		$this->customNewModel = $customNewModel;
		$this->customFileModel = $customFileModel;
	}

	protected function build(): void
	{
		$this->model = $this->customNewModel;

		parent::build();

		$this->setDefaultOrderBy('created', true);
		$this->setFilterFactory([$this, 'gridFilterFactory']);

		$this->addColumn('title', 'Nadpis');
		$this->addColumn('contents', 'Obsah');

		if ($this->presenter->configuration->getLanguagesCount() > 1) {
			$this->addColumn('lang', 'Jazyk');
		}

		$this->addColumn('created', 'Vytvořeno')->setSortable();
		$this->addColumn('public', 'Veřejná');
		$this->addColumn('gallery_id', 'Galerie');

		$this->addRowAction('edit', 'Upravit', static function (): void {});
		$this->addRowAction('delete', 'Smazat', static function (ActiveRow $record): void {
			if ($record->cover) {
				unlink(WWW . '/upload/custom_new/' . $record->id . '/' . $record->cover);
			}

			$record->delete();
		})
			->setProtected(false)
			->setConfirmation('Opravdu chcete aktualitu smazat?');

		$this->hotFilters = ['title', 'public'];
	}

	public function gridFilterFactory(Container $c): void
	{
		$c->addText('title', 'Nadpis')->setHtmlAttribute('placeholder', 'Filtrovat dle nadpisu');
		$c->addSelect('public')
			->setPrompt('Zveřejněno')
			->setItems([0 => 'Ne', 1 => 'Ano']);
	}
}
