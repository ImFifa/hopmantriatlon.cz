<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;

class CategoryModel extends BaseModel
{
	protected string $table = 'category';


    public function getCategories(): array
    {
        return $this->getTable()->fetchAll();
    }

    public function getCategoryById(int $category_id): ActiveRow
    {
        return $this->getTable()->where('id', $category_id)->fetch();
    }
}
