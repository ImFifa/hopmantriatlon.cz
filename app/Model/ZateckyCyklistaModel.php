<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class ZateckyCyklistaModel extends BaseModel
{
	protected string $table = 'zatecky_cyklista';

	public function getRegisteredCompetitors(): ?Selection
	{
		return $this->getTable()->order('id ASC');
	}
}
