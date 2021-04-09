<?php declare(strict_types=1);

namespace App\AdminModule\Grid;

use App\Model\EventGalleryModel;
use K2D\Core\AdminModule\Grid\BaseV2Grid;

use Nette;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Container;

class EventGalleryGrid extends BaseV2Grid
{

	private EventGalleryModel $eventGalleryModel;

	public function __construct(EventGalleryModel $eventGalleryModel)
	{
		parent::__construct();
		$this->eventGalleryModel = $eventGalleryModel;
	}

	protected function build(): void
	{
		$this->model = $this->eventGalleryModel;

		parent::build();

		$this->setDefaultOrderBy('id', true);
		$this->setFilterFactory([$this, 'gridFilterFactory']);

		$this->addColumn('id', '#');
		$this->addColumn('year', 'Rok pořízení');
		$this->addColumn('name', 'Název');
		$this->addColumn('link', 'Odkaz');
		$this->addColumn('event_id', 'ID události');
		$this->addColumn('author', 'Autor');
		$this->addColumn('n_photos', 'Počet fotek');
		$this->addColumn('public', 'Veřejná');

		$this->addRowAction('edit', 'Upravit', static function (): void {});
		$this->addRowAction('delete', 'Smazat', static function (ActiveRow $record): void {
			if ($record->cover) {
				unlink(WWW . '/upload/eventGalleries/' . $record->id . '/' . $record->cover);
			}

			$record->delete();
		})
			->setProtected(false)
			->setConfirmation('Opravdu chcete smazat galerii?');

		$this->hotFilters = ['name', 'public'];
	}

	public function gridFilterFactory(Container $c): void
	{
		$c->addText('name', 'Název galerie')->setHtmlAttribute('placeholder', 'Filtrovat dle názvu');
		$c->addSelect('public')
			->setPrompt('Zveřejněno')
			->setItems([0 => 'Ne', 1 => 'Ano']);
	}
}
