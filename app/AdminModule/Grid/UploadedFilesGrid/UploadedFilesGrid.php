<?php declare(strict_types=1);

namespace App\AdminModule\Grid\UploadedFilesGrid;

use App\Model\CustomFileModel;
use App\Model\CustomNewModel;
use App\Model\EventGalleryModel;
use K2D\Core\AdminModule\Grid\BaseGrid;
use K2D\Core\AdminModule\Grid\BaseV2Grid;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Forms\Container;
use TwiGrid\Components\Column;

class UploadedFilesGrid extends BaseGrid
{
	private CustomFileModel $customFileModel;

	public function __construct(CustomFileModel $customFileModel)
	{
		parent::__construct();
		$this->customFileModel = $customFileModel;
	}

	protected function build(): void
	{
		$this->model = $this->customFileModel;

		parent::build();

		$this->addColumn('id', 'ID')->setSortable();
		$this->addColumn('path', 'Soubor');
		$this->addColumn('name', 'Název');
		$this->addColumn('created', 'Datum nahrání')->setSortable();

		$this->setInlineEditing([$this, 'inlineEditFactory'], static function (ActiveRow $record, array $data) {
			$record->update($data);
		});

		$this->addRowAction('delete', 'Smazat', static function (ActiveRow $record): void {
			unlink(WWW . '/upload/custom_new/' . $record->folder_id . '/files/' . $record->path);

			$record->delete();
		})
			->setConfirmation('Opravdu chcete smazat tento soubor?');

		$this->setDataLoader(function (array $filters, array $order) {
			$files = $this->customFileModel->getFiles();

			// sorting
			foreach ($order as $column => $dir) {
				$files->order('id DESC');
			}

			return $files;
		});

	}

	public function inlineEditFactory(Container $c, ActiveRow $record): void
	{
		$c->addText('name');
		$c->setDefaults($record);
	}
}
