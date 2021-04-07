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

	public function getRegisteredCompetitors($event_id): int
	{
		return $this->getTable()->where('event_id', $event_id)->count();
	}

	public function getRegisteredMan($event_id): int
	{
		return $this->getTable()->where('event_id', $event_id)->where('sex', 'M')->count();
	}

	public function getRegisteredWoman($event_id): int
	{
		return $this->getTable()->where('event_id', $event_id)->where('sex', 'Ž')->count();
	}
//
//	public function getEvent(string $slug): ?ActiveRow
//	{
//		return $this->getTable()->where('slug', $slug)->fetch();
//	}

}
