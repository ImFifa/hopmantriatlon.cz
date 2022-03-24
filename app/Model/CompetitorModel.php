<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class CompetitorModel extends BaseModel
{

	protected string $table = 'competitor';


    public function getCompetitor(int $id): ActiveRow
    {
        return $this->getTable()->where('id', $id)->fetch();
    }

	public function getCompetitors(int $competition_id): Selection
    {
		return $this->getTable()->where('competition_id', $competition_id);
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

	public function getRegisteredKids($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('category_id', 1)->count();
	}

    // pulmaraton
	public function getRegistered10Run($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance_id', 2)->count();
	}
	public function getRegistered21Run($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance_id', 1)->count();
	}

	// triatlon
	public function getRegisteredSprintTriathlon($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance_id', 5)->count();
	}
	public function getRegisteredOlympicTriathlon($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance_id', 4)->count();
	}

	// advent
	public function getRegisteredJuniorsAdvent($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance_id', 9)->count();
	}
}
