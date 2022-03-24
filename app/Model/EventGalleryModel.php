<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class EventGalleryModel extends BaseModel
{
	protected string $table = 'competition_gallery';

	public function getPublicEventGalleries($event_id): Selection
	{
		return $this->getTable()->where('public', 1)->where('event_id', $event_id)->order('year DESC')->order('id DESC');
	}

	public function getPublicEventGalleriesFromYear(int $event_id, int $year): Selection
	{
		return $this->getTable()->where('public', 1)->where('event_id', $event_id)->where('year', $year)->order('year DESC')->order('id DESC');
	}
}
