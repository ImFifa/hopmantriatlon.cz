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
		return $this->getTable()->where('competition_id', $competition_id)->order('distance DESC')->order('surname ASC');
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


	// pulmaraton
	public function getRegisteredKidsRun($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', 'Dětský běh')->count();
	}
	public function getRegistered10Run($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', 'Desítka')->count();
	}
	public function getRegistered21Run($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', 'Půlmaraton')->count();
	}


	// triatlon
	public function getRegisteredKidsTriatlon($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', 'Dětský triatlon')->count();
	}
	public function getRegisteredSprintTriathlon($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', 'Sprint triatlon')->count();
	}
	public function getRegisteredOlympicTriathlon($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', 'Olympijský triatlon')->count();
	}

	// advent
	public function getRegisteredKidsAdvent($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', '400m')->count();
	}
	public function getRegisteredJuniorsAdvent($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('distance', '1000m')->count();
	}
	public function getRegisteredWomenAdvent($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('sex', 'Ž')->count();
	}
	public function getRegisteredMenAdvent($competition_id): int
	{
		return $this->getTable()->where('competition_id', $competition_id)->where('sex', 'M')->count();
	}

//
//	public function getEvent(string $slug): ?ActiveRow
//	{
//		return $this->getTable()->where('slug', $slug)->fetch();
//	}

}
