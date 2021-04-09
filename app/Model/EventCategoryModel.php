<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;

class EventCategoryModel extends BaseModel
{
	protected string $table = 'event_category';

	public function getCategoryNameById(int $category_id): string
	{
		return $this->getTable()->where('id', $category_id)->getName();
	}

	public function getCategoriesForEventById(int $event_id): array
	{
		return $this->getTable()->where('event_id', $event_id)->fetchAll();
	}

	public function getForSelectByCode(string $key = 'code', string $var = 'name'): array
	{
		return $this->getTable()->fetchPairs($key, $var);
	}

}
