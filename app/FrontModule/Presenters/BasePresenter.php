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
        $this->template->facebook = $vars['facebook'];
        $this->template->instagram = $vars['instagram'];
        $this->template->youtube = $vars['youtube'];
        $this->template->description = $vars['description'];
        $this->template->og_image = $vars['og_image'];
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
