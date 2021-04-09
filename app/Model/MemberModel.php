<?php

namespace App\Model;

use K2D\Core\Models\BaseModel;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class MemberModel extends BaseModel
{
	protected string $table = 'team_member';

	public function getMember(string $slug): ?ActiveRow
	{
		return $this->getTable()->where('public', 1)->where('slug', $slug)->fetch();
	}

	public function getPublicMembers(): Selection
	{
		return $this->getTable()->where('public', 1)->order('created DESC')->order('id DESC');
	}

	public function getPublicMembersCount(): int
	{
		return $this->getTable()->where('public', 1)->count();
	}

}
