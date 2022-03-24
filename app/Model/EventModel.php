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

	public function getActiveEvents(): array
	{
		return $this->getTable()->where('active', 1)->fetchAll();
	}

	public function getInactiveEvents(): array
	{
		return $this->getTable()->where('active', 0)->fetchAll();
	}

    public function getEvent(string $slug): ?ActiveRow
    {
        return $this->getTable()->where('slug', $slug)->fetch();
    }

    public function getEventById(int $event_id): ?ActiveRow
    {
        return $this->getTable()->where('id', $event_id)->fetch();
    }

}
