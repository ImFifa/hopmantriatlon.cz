<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class CompetitorModel extends BaseModel
{

	protected string $table = 'competitor';

	public function getCompetitors($competition_id): Selection
	{
		return $this->getTable()->where('competition_id', $competition_id)->order('id DESC');
	}

	public function getRegisteredCompetitors($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->count();
	}

	public function getRegisteredMan($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('sex', 'M')->count();
	}

	public function getRegisteredWoman($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('sex', 'Ž')->count();
	}
//
//	public function getEvent(string $slug): ?ActiveRow
//	{
//		return $this->getTable()->where('slug', $slug)->fetch();
//	}

}
