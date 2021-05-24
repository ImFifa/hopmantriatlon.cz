<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class RelayModel extends BaseModel
{
	protected string $table = 'relay';

	public function getRelays($competition_id): Selection
	{
		return $this->getTable()->where('competition_id', $competition_id)->order('id DESC');
	}

	public function getRegisteredRelays($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->count();
	}
}
