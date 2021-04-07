<?php

namespace App\Service;

use App\Model\EventModel;
use K2D\Core\Models\ConfigurationModel;
use K2D\Core\Models\LogModel;
use K2D\Core\Service\ModelRepository;
use K2D\File\Model\FileModel;
use K2D\News\Models\NewModel;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property-read EventModel $event
 * @property-read NewModel $new
 * @property-read FileModel $file
 */
class ProjectModelRepository extends ModelRepository
{
	public function getPublicNewsCount(string $lang): int
	{
		return $this->new->getTable()->where('public', 1)->where('lang', $lang)->count();
	}
	public function getPrevPublicNew(DateTime $created): ?ActiveRow
	{
		return $this->new->getTable()->where('public', 1)->where('created <', new DateTime($created))->order('created DESC')->order('id DESC')->fetch();
	}
	public function getNextPublicNew(DateTime $created): ?ActiveRow
	{
		return $this->new->getTable()->where('public', 1)->where('created >', new DateTime($created))->order('created ASC')->order('id DESC')->fetch();
	}

	// FILE
	public function getFilesDESC(?int $folderId = null, bool $baseOnly = true): array
	{
		$sql = $this->file->getTable()->where('folder_id', $folderId)->order('id DESC');

		if ($baseOnly) {
			$sql->where('base', 1);
		}

		return $sql->fetchAll();
	}
}
