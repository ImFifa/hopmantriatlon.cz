<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;

class EventModel extends BaseModel
{

	protected string $table = 'events';

	public function getEvents(): array
	{
		return $this->getTable()->fetchAll();
	}

	public function getEvent(string $slug): ?ActiveRow
	{
		return $this->getTable()->where('slug', $slug)->fetch();
	}

}
