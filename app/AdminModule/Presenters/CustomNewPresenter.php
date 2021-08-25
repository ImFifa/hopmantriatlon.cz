<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Grid\UploadedFilesGrid\UploadedFilesGrid;
use App\AdminModule\Grid\UploadedFilesGrid\UploadedFilesGridFactory;

use App\Model\CustomFileModel;
use App\Model\CustomNewModel;

use K2D\Core\AdminModule\Component\CropperComponent\CropperComponent;
use K2D\Core\AdminModule\Component\CropperComponent\CropperComponentFactory;

use K2D\Core\AdminModule\Presenter\BasePresenter;
use K2D\Core\Helper\Helper;

use App\AdminModule\Component\FileDropzoneComponent\FileDropzoneComponent;
use App\AdminModule\Component\FileDropzoneComponent\FileDropzoneComponentFactory;
use K2D\File\AdminModule\Component\DropzoneComponent\DropzoneComponent;
use K2D\File\AdminModule\Component\DropzoneComponent\DropzoneComponentFactory;

use K2D\Gallery\Models\GalleryModel;
use App\AdminModule\Grid\CustomNewGrid;
use App\AdminModule\Grid\CustomNewGridFactory;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Http\FileUpload;
use Nette\Http\SessionSection;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * @property-read ActiveRow|null $customNew
 */
class CustomNewPresenter extends BasePresenter
{

	/** @inject */
	public CustomNewModel $customNewModel;

	/** @inject */
	public GalleryModel $galleries;

	/** @inject */
	public CustomFileModel $customFileModel;

	/** @var CustomNewGridFactory @inject */
	public CustomNewGridFactory $customNewGridFactory;

	/** @inject */
	public DropzoneComponentFactory $dropzoneComponentFactory;

	/** @inject */
	public FileDropzoneComponentFactory $fileDropzoneComponentFactory;

	/** @inject */
	public UploadedFilesGridFactory $uploadedFilesGridFactory;

	/** @inject */
	public CropperComponentFactory $cropperComponentFactory;

	public function renderEdit(?int $id = null): void
	{
		$this->template->customNew = null;

		if ($id !== null && $this->customNew !== null) {
			$customNew = $this->customNew->toArray();

			$this->template->files = $this->customFileModel->getFilesByNewId($this->customNew->id);

			/** @var DateTime $date */
			$date = $customNew['created'];
			$customNew['created'] = $date->format('j.n.Y');
			$form = $this['editForm'];
			$form->setDefaults($customNew);

			$this->template->customNew = $this->customNew;
		}
	}

	public function createComponentEditForm(): Form
	{
		$form = new Form();

		$form->addText('title', 'Nadpis:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 255)
			->setRequired('Musíte uvést nadpis novinky');

		$form->addText('created', 'Datum:')
			->setDefaultValue((new DateTime())->format('j.n.Y'))
			->setRequired('Musíte uvést datum novinky');

		$form->addCheckbox('public', 'Zveřejnit')
			->setDefaultValue(true);

		$form->addSelect('gallery_id', 'Připojit galerii:')
			->setPrompt('Žádná')
			->setItems($this->galleries->getForSelect());

		$form->addTextArea('perex', 'Perex', 100, 5)
			->setHtmlAttribute('class', 'form-wysiwyg');

		$form->addTextArea('content', 'Obsah', 100, 25)
			->setHtmlAttribute('class', 'form-wysiwyg');

		$form->addSubmit('save', 'Uložit');

		$form->onSubmit[] = function (Form $form) {
			$values = $form->getValues(true);
			$values['created'] = date_create_from_format('j.n.Y', $values['created'])->setTime(0, 0);
			$values['slug'] = Strings::webalize($values['title']);

			$customNew = $this->customNew;

			if ($customNew === null) {
				$customNew = $this->customNewModel->insert($values);
				$this->flashMessage('Aktualita vytvořena');
			} else {
				$customNew->update($values);
				$this->flashMessage('Aktualita upravena');
			}

			$customNew->update([
				'slug' => $customNew->id . '-' . $values['slug']
			]);

			$this->redirect('this', ['id' => $customNew->id]);
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
			$link = WWW . '/upload/custom_new/' . $this->customNew->id . '/';
			$fileName = Helper::generateFileName($fileUpload);

			if (!file_exists($link)) {
				Helper::mkdir($link);
			}

			if ($image->getHeight() > 1080 || $image->getWidth() > 1920) {
				$image->resize(1920, 1080);
			}

			$image->save($link . $fileName);
			$this->customNew->update(['cover' => $fileName]);
		}
	}

	public function handleRedrawFiles(): void
	{
		$this->redirect('this');
	}

	public function handleUploadFileFiles(): void
	{
		$files = $this->getHttpRequest()->getFiles();

		$this->increaseFileUploadTotalCount();

		/** @var FileUpload $file */
		foreach ($files as $file) {
			if ($file->isOk()) {
				$existingFile = $this->customFileModel->getTable()->where('folder_id', $this->customNew->id)->where('path', $file->getSanitizedName())->fetch();

				if ($existingFile) {
					$this->flashFileUploadMessage('Soubor s názvem "' . $file->getSanitizedName() . '" již existuje.', 'danger');
					continue;
				}

				$link = WWW . '/upload/custom_new/' . $this->customNew->id . '/files/';
				if (!file_exists($link)) {
					Helper::mkdir($link);
				}

				$this->customFileModel->insert([
					'folder_id' => $this->customNew->id,
					'path' => $file->getSanitizedName(),
					'name' => $file->getName(),
					'type' => strtolower(Helper::getFileType($file))
				]);
				$file->move(WWW . '/upload/custom_new/' .$this->customNew->id . '/files/' . $file->getSanitizedName());

				$this->increaseFileUploadSuccessCount();
			}
		}
	}

	public function handleRedrawFileFiles(): void
	{
		$session = $this->getFileFlashSession();

		if (isset($session['messages'])) {
			foreach ($session->messages as $message) {
				$this->flashMessage($message['message'], $message['type']);
			}
		}

		if (isset($session['success'])) {
			$this->flashMessage(sprintf('Úspěšně nahráno: %s/%s', $session['success'], $session['total']));
		} else {
			$this->flashMessage('Nebyl nahrán žádný nový soubor.', 'warning');
		}

		$session->remove();

		$this->redrawControl('files');
		$this->redrawControl('flashes');
	}

	private function flashFileUploadMessage(string $message, string $type = 'success'): void
	{
		$this->getFileFlashSession()->messages[] = ['message' => $message, 'type' => $type];
	}

	private function increaseFileUploadTotalCount(): void
	{
		++$this->getFileFlashSession()->total;
	}

	private function increaseFileUploadSuccessCount(): void
	{
		++$this->getFileFlashSession()->success;
	}

	private function getFileFlashSession(): SessionSection
	{
		return $this->getSession('FileFlashSesionSection');
	}

	private function deleteFile(ActiveRow $file): void
	{
		unlink(WWW . '/upload/custom_new/' . $this->customNew->id . '/files/' . $file->path);
		$file->delete();
	}

	public function handleDeleteFile($fileId): void
	{
		$file = $this->fileModel->get($fileId);

		if (!$file) {
			return;
		}

		$this->deleteFile($file);

		$this->flashMessage('Soubor smazán');
		$this->redrawControl('files');
	}

	public function handleRenameFile($fileId): void
	{
		$file = $this->fileModel->get($fileId);

		if (!$file) {
			return;
		}

		$this->deleteFile($file);

		$this->flashMessage('Soubor smazán');
		$this->redrawControl('files');
	}

	public function handleCropImage(): void
	{
		$this->showModal('cropper');
	}

	public function handleDeleteImage(): void
	{
		unlink(WWW . '/upload/custom_new/' . $this->customNew->id . '/' . $this->customNew->cover);
		$this->customNew->update(['cover' => null]);
		$this->flashMessage('Náhledový obrázek byl smazán');
		$this->redirect('this');
	}

	protected function createComponentCustomNewGrid(): CustomNewGrid
	{
		return $this->customNewGridFactory->create();
	}

	protected function createComponentUploadedFilesGrid(): UploadedFilesGrid
	{
		return $this->uploadedFilesGridFactory->create();
	}

	protected function createComponentDropzone(): DropzoneComponent
	{
		$control = $this->dropzoneComponentFactory->create();
		$control->setPrompt('Nahrajte obrázek přetažením nebo kliknutím sem.');
		$control->setUploadLink($this->link('uploadFiles!'));
		$control->setRedrawLink($this->link('redrawFiles!'));

		return $control;
	}

	protected function createComponentFileDropzone(): FileDropzoneComponent
	{
		$control = $this->fileDropzoneComponentFactory->create();
		$control->setPrompt('Nahrajte soubory přetažením nebo kliknutím sem.');
		$control->setUploadLink($this->link('uploadFileFiles!'));
		$control->setRedrawLink($this->link('redrawFileFiles!'));

		return $control;
	}

	protected function createComponentCropper(): CropperComponent
	{
		$cropper = $this->cropperComponentFactory->create();

		if ($this->customNew->cover !== null) {
			$cropper->setImagePath('upload/custom_new/' . $this->customNew->id . '/' . $this->customNew->cover)
				->setAspectRatio((float) $this->configuration->newAspectRatio);
		}

		$cropper->onCrop[] = function (): void {
			$this->redirect('this');
		};

		return $cropper;
	}

	protected function getCustomNew(): ?ActiveRow
	{
		return $this->customNewModel->get($this->getParameter('id'));
	}

}
