<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use K2D\Box\Component\BoxComponent\BoxComponent;
use K2D\Box\Component\BoxComponent\BoxComponentFactory;
use K2D\Core\Models\ConfigurationModel;
use K2D\Core\Presenter\FrontBasePresenter;
use stdClass;

abstract class BasePresenter extends FrontBasePresenter
{

	/** @inject */
	public BoxComponentFactory $boxFactory;

	/** @var ConfigurationModel */
	public ConfigurationModel $configuration;

	public function beforeRender(): void
	{
		parent::beforeRender();
		$vars = $this->configuration->getAllVars();
		$desc = $vars['description'];
		$ig = $vars['instagram'];
		$fb = $vars['facebook'];
		$yt = $vars['youtube'];
		$this->template->desc = $desc;
		$this->template->ig = $ig;
		$this->template->fb = $fb;
		$this->template->yt = $yt;
	}

	public function flashMessage($message, string $type = 'success'): stdClass
	{
		return parent::flashMessage($message, $type);
	}

	protected function createComponentBox(): BoxComponent
	{
		return $this->boxFactory->create();
	}

}
