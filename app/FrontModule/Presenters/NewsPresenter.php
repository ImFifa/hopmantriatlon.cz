<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use K2D\Gallery\Models\ImageModel;
use K2D\News\Models\NewModel;
use Nette\Utils\Paginator;

class NewsPresenter extends BasePresenter
{

	/** @inject */
	public NewModel $newsModel;

	/** @inject */
	public ImageModel $imageModel;

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

		if (!$new) {
			$this->error();
		} else {
			$this->template->new = $new;

			// event gallery
			if($new->gallery_id != NULL) {
				$this->template->images = $this->imageModel->getImagesByGallery($new->gallery_id);
			}

			$this->template->prevNew = $this->repository->getPrevPublicNew($new->created);
			$this->template->nextNew = $this->repository->getNextPublicNew($new->created);
		}
	}

}
