<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\MemberModel;
use K2D\Gallery\Models\ImageModel;
use Nette\Utils\Paginator;

class MemberPresenter extends BasePresenter
{

	/** @inject */
	public MemberModel $memberModel;

	/** @inject */
	public ImageModel $imageModel;

	public function renderDefault(int $page = 1): void
	{
		$publicMembersCount = $this->memberModel->getPublicMembersCount();
		$this->template->membersCount = $publicMembersCount;

		$vars = $this->configuration;
		$itemsPerPage = (int) $vars->itemsPerPage;

		$paginator = new Paginator;
		$paginator->setPage($page); // číslo aktuální stránky
		$paginator->setItemsPerPage($itemsPerPage); // počet položek na stránce
		$paginator->setItemCount($publicMembersCount); // celkový počet položek, je-li znám

		$this->template->news = $this->memberModel->getPublicMembers()->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
	}

	public function renderShow($slug): void
	{
		$member = $this->memberModel->getMember($slug);

		if (!$member) {
			$this->error();
		} else {
			$this->template->member = $member;
		}
	}

}
