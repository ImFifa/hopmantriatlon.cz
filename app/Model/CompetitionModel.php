<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;

class CompetitionModel extends BaseModel
{

	protected string $table = 'competition';

	public function getCompetitionById($id): ActiveRow
	{
		return $this->getTable()->where('id', $id)->fetch();
	}

	public function getSelectedCompetition($slug): ActiveRow
	{
		return $this->getTable()->where('slug', $slug)->fetch();
	}

	public function getThisYearsActiveCompetitionsById($id, $year): array
	{
		return $this->getTable()->where('registration_active', 1)->where('event_id', $id)->where('year', $year)->order('id ASC')->fetchAll();
	}
//
//	public function getEvent(string $slug): ?ActiveRow
//	{
//		return $this->getTable()->where('slug', $slug)->fetch();
//	}

}
