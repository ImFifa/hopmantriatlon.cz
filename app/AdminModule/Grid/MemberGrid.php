<?php declare(strict_types=1);

namespace App\AdminModule\Grid;

use App\Model\MemberModel;
use K2D\Core\AdminModule\Grid\BaseV2Grid;

use Nette;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Container;

class MemberGrid extends BaseV2Grid
{

	private MemberModel $memberModel;

	public function __construct(MemberModel $memberModel)
	{
		parent::__construct();
		$this->memberModel = $memberModel;
	}

	protected function build(): void
	{
		$this->model = $this->memberModel;

		parent::build();

		$this->setDefaultOrderBy('surname', true);
		$this->setFilterFactory([$this, 'gridFilterFactory']);

		$this->addColumn('name', 'Jméno');
		$this->addColumn('surname', 'Příjmení');
		$this->addColumn('sport', 'Hlavní sport');
		$this->addColumn('updated', 'Poslední úprava')->setSortable();
		$this->addColumn('public', 'Veřejná');

		$this->addRowAction('edit', 'Upravit', static function (): void {});
		$this->addRowAction('delete', 'Smazat', static function (ActiveRow $record): void {
			if ($record->profile) {
				unlink(WWW . '/upload/members/' . $record->id . '/' . $record->profile);
			}

			$record->delete();
		})
			->setProtected(false)
			->setConfirmation('Opravdu chcete smazat profil člena týmu?');

		$this->hotFilters = ['surname', 'sport', 'public'];
	}

	public function gridFilterFactory(Container $c): void
	{
		$c->addText('surname', 'Příjmení')->setHtmlAttribute('placeholder', 'Filtrovat dle příjmení');
		$c->addSelect('sport')
			->setPrompt('Filtrovat dle hlavního sportu')
			->setItems([0 => 'Triatlon', 1 => 'Běh', 2 => 'Cyklistika']);
		$c->addSelect('public')
			->setPrompt('Zveřejněno')
			->setItems([0 => 'Ne', 1 => 'Ano']);
	}
}
