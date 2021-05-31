<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;

class EventCategoryModel extends BaseModel
{
	protected string $table = 'event_category';

	public function getCategoryNameById(int $category_id): string
	{
		return $this->getTable()->where('id', $category_id)->select('name');
	}

	public function getCategoryById(int $category_id): ?ActiveRow
	{
		return $this->getTable()->where('id', $category_id)->fetch();
	}

	public function getCategoriesForEventById(int $event_id): array
	{
		return $this->getTable()->select('id, code, name')->where('event_id', $event_id)->fetchAll();
	}

	public function getDistancesForEventById(int $event_id): array
	{
		return $this->getTable()->select('id, distance')->where('event_id', $event_id)->group('distance')->fetchAll();
	}

	public function getForSelectByCode(string $key = 'code', string $var = 'name'): array
	{
		return $this->getTable()->fetchPairs($key, $var);
	}

	public function getForSelectByEventId(int $event_id, string $key = 'id', string $var = 'name'): array
	{
		return $this->getTable()->where('event_id', $event_id)->fetchPairs($key, $var);
	}

}
