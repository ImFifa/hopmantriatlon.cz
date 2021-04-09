<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Grid\EventGalleryGridFactory;
use App\AdminModule\Grid\EventGalleryGrid;
use App\Model\EventGalleryModel;
use App\Model\EventModel;
use K2D\Core\AdminModule\Component\CropperComponent\CropperComponent;
use K2D\Core\AdminModule\Component\CropperComponent\CropperComponentFactory;
use K2D\Core\AdminModule\Presenter\BasePresenter;
use K2D\Core\Helper\Helper;
use K2D\File\AdminModule\Component\DropzoneComponent\DropzoneComponent;
use K2D\File\AdminModule\Component\DropzoneComponent\DropzoneComponentFactory;
use K2D\File\Model\FileModel;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;


/**
 * @property-read ActiveRow|null $eventGallery
 */
class EventGalleryPresenter extends BasePresenter
{
	/** @inject */
	public EventGalleryModel $eventGalleryModel;

	/** @inject */
	public EventModel $eventModel;

	/** @inject */
	public FileModel $fileModel;

	/** @var EventGalleryGridFactory @inject */
	public EventGalleryGridFactory $eventGalleryGridFactory;

	/** @inject */
	public DropzoneComponentFactory $dropzoneComponentFactory;

	/** @inject */
	public CropperComponentFactory $cropperComponentFactory;

	public function renderEdit(?int $id = null): void
	{
		$this->template->eventGallery = null;

		if ($id !== null && $this->eventGallery !== null) {
			$eventGallery = $this->eventGallery->toArray();

			$form = $this['editForm'];
			$form->setDefaults($eventGallery);

			$this->template->eventGallery = $this->eventGallery;
		}
	}

	public function createComponentEditForm(): Form
	{
		$form = new Form();

		$form->addText('name', 'Název:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100)
			->setRequired('Název je povinný');

		$form->addText('link', 'Odkaz:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 200)
			->setRequired('Odkaz je povinný');

		$form->addSelect('event_id', 'Událost:')
			->setPrompt('-------')
			->setItems($this->eventModel->getForSelect())
			->setRequired('Volba události je povinná');

		$form->addInteger('n_photos', 'Počet fotek:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 11);

		$form->addText('author', 'Autor:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 100);

		$form->addInteger('year', 'Rok pořízení:')
			->addRule(Form::LENGTH, 'Délka jsou %s znaky', 4)
			->setRequired('Rok pořízení je povinný');

		$form->addCheckbox('public', 'Zveřejnit')
			->setDefaultValue(false);

		$form->addSubmit('save', 'Uložit');

		$form->onSubmit[] = function (Form $form) {
			$values = $form->getValues(true);
			$eventGallery = $this->eventGallery;

			if ($eventGallery === null) {
				$eventGallery = $this->eventGalleryModel->insert($values);
				$this->flashMessage('Galerie závodu vytvořena');
			} else {
				$eventGallery->update($values);
				$this->flashMessage('Galerie závodu upravena');
			}

			$this->redirect('this', ['id' => $eventGallery->id]);
		};

		return $form;
	}

	public function handleUploadFiles(): void
	{
		$fileUploads = $this->getHttpRequest()->getFiles();
		$fileUpload = reset($fileUploads);

		if (!($fileUpload instanceof FileUpload)) {
			return;
		}

		if ($fileUpload->isOk() && $fileUpload->isImage()) {
			$image = $fileUpload->toImage();
			$link = WWW . '/upload/eventGalleries/' . $this->eventGallery->id . '/';
			$fileName = Helper::generateFileName($fileUpload);

			if (!file_exists($link)) {
				Helper::mkdir($link);
			}

			if ($image->getHeight() > 400 || $image->getWidth() > 400) {
				$image->resize(400, 400);
			}

			$image->save($link . $fileName);
			$this->eventGallery->update(['cover' => $fileName]);
		}
	}

	public function handleRedrawFiles(): void
	{
		$this->redirect('this');
	}

	public function handleCropImage(): void
	{
		$this->showModal('cropper');
	}

	public function handleDeleteImage(): void
	{
		unlink(WWW . '/upload/eventGalleries/' . $this->eventGallery->id . '/' . $this->eventGallery->cover);
		$this->eventGallery->update(['cover' => null]);
		$this->flashMessage('Náhledový obrázek byl smazán');
		$this->redirect('this');
	}

	protected function createComponentEventGalleryGrid(): EventGalleryGrid
	{
		return $this->eventGalleryGridFactory->create();
	}

	protected function createComponentDropzone(): DropzoneComponent
	{
		$control = $this->dropzoneComponentFactory->create();
		$control->setPrompt('Nahrajte obrázek přetažením nebo kliknutím sem.');
		$control->setUploadLink($this->link('uploadFiles!'));
		$control->setRedrawLink($this->link('redrawFiles!'));

		return $control;
	}

	protected function createComponentCropper(): CropperComponent
	{
		$cropper = $this->cropperComponentFactory->create();

		if ($this->eventGallery->cover !== null) {
			$cropper->setImagePath('upload/eventGalleries/' . $this->eventGallery->id . '/' . $this->eventGallery->cover)
				->setAspectRatio((float) 1);
		}

		$cropper->onCrop[] = function (): void {
			$this->redirect('this');
		};

		return $cropper;
	}

	protected function getEventGallery(): ?ActiveRow
	{
		return $this->eventGalleryModel->get($this->getParameter('id'));
	}
}
