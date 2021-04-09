<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class EventGalleryModel extends BaseModel
{
	protected string $table = 'event_gallery';

	public function getPublicEventGalleries(): Selection
	{
		return $this->getTable()->where('public', 1)->order('year DESC')->order('id DESC');
	}

	public function getPublicEventGalleriesFromYear(int $year): Selection
	{
		return $this->getTable()->where('public', 1)->where('year', $year)->order('year DESC')->order('id DESC');
	}
}
