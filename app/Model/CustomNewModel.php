<?php declare(strict_types=1);

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class CustomNewModel extends BaseModel
{

	protected string $table = 'custom_new';

	public function getNew(string $slug, string $lang): ?ActiveRow
	{
		return $this->getTable()->where('public', 1)->where('lang', $lang)->where('slug', $slug)->fetch();
	}

	public function getPublicNews(): Selection
	{
		return $this->getTable()->where('public', 1)->order('created DESC')->order('id DESC');
	}

}
