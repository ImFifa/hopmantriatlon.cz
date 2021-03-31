<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use K2D\Box\Component\BoxComponent\BoxComponent;
use K2D\Box\Component\BoxComponent\BoxComponentFactory;
use K2D\Core\Presenter\FrontBasePresenter;
use stdClass;

abstract class BasePresenter extends FrontBasePresenter
{

	/** @inject */
	public BoxComponentFactory $boxFactory;

	public function flashMessage($message, string $type = 'success'): stdClass
	{
		return parent::flashMessage($message, $type);
	}

	protected function createComponentBox(): BoxComponent
	{
		return $this->boxFactory->create();
	}

}
