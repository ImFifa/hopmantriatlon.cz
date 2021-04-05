<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;

class CompetitorModel extends BaseModel
{

	protected string $table = 'competitor';

	public function getCompetitors($event_id): array
	{
		return $this->getTable()->where('event_id', $event_id)->order('id DESC')->fetchAll();
	}
//
//	public function getEvent(string $slug): ?ActiveRow
//	{
//		return $this->getTable()->where('slug', $slug)->fetch();
//	}

}
