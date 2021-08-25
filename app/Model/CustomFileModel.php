<?php declare(strict_types=1);

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class CustomFileModel extends BaseModel
{

	protected string $table = 'custom_file';

	public function getFiles(): Selection
	{
		$sql = $this->getTable();
		return $sql;
	}

	public function getFilesByNewId(int $new_id): array
	{
		return $this->getTable()->where('folder_id', $new_id)->fetchAll();
	}
}
