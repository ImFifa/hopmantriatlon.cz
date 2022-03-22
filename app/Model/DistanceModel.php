<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class DistanceModel extends BaseModel
{

    protected string $table = 'distance';

    public function getDistances(): array
    {
        return $this->getTable()->fetchAll();
    }

    public function getDistanceById(int $id): ActiveRow
    {
        return $this->getTable()->where('id', $id)->fetch();
    }

    public function getDistanceNameById(int $id): string
    {
        return $this->getTable()->where('id', $id)->getName();
    }

    public function getDistancesByCompetition(int $competition_id): array
    {
        return $this->getTable()->where('competition_id', $competition_id)->fetchPairs('id','name');
    }

    public function getDistancesByCompetitionAssoc(int $competition_id): array
    {
        return $this->getTable()->where('competition_id', $competition_id)->fetchAssoc('id');
    }

}
