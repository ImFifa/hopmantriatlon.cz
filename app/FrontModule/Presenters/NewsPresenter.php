<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use K2D\News\Models\NewModel;
use Nette\Utils\Paginator;

class NewsPresenter extends BasePresenter
{

	/** @inject */
	public NewModel $newsModel;

	public function renderDefault(int $page = 1): void
	{
		$publicNewsCount = $this->repository->getPublicNewsCount('cs');
		$this->template->newsCount = $publicNewsCount;

		$vars = $this->configuration;
		$itemsPerPage = (int) $vars->itemsPerPage;

		$paginator = new Paginator;
		$paginator->setPage($page); // číslo aktuální stránky
		$paginator->setItemsPerPage($itemsPerPage); // počet položek na stránce
		$paginator->setItemCount($publicNewsCount); // celkový počet položek, je-li znám

		$this->template->news = $this->newsModel->getPublicNews('cs')->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
	}

	public function renderShow($slug): void
	{
		$new = $this->newsModel->getNew($slug, 'cs');
		$this->template->new = $new;
		$this->template->prevNew = $this->repository->getPrevPublicNew($new->created);
		$this->template->nextNew = $this->repository->getNextPublicNew($new->created);
		bdump($this->template->prevNew, 'prev');
		bdump($this->template->nextNew, 'next');
	}

}
