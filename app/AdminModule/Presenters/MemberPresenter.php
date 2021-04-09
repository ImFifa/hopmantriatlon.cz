<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Grid\MemberGridFactory;
use App\AdminModule\Grid\MemberGrid;
use App\Model\MemberModel;
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
use Nette\Utils\DateTime;
use Nette\Utils\Strings;


/**
 * @property-read ActiveRow|null $member
 */
class MemberPresenter extends BasePresenter
{
	/** @inject */
	public MemberModel $memberModel;

	/** @inject */
	public FileModel $fileModel;

	/** @var MemberGridFactory @inject */
	public $memberGridFactory;

	/** @inject */
	public DropzoneComponentFactory $dropzoneComponentFactory;

	/** @inject */
	public CropperComponentFactory $cropperComponentFactory;

	public function renderEdit(?int $id = null): void
	{
		$this->template->member = null;

		if ($id !== null && $this->member !== null) {
			$member = $this->member->toArray();

			/** @var DateTime $date */
			$date = $member['created'];
			$member['created'] = $date->format('j.n.Y');
			$form = $this['editForm'];
			$form->setDefaults($member);

			$this->template->member = $this->member;
		}
	}

	public function createComponentEditForm(): Form
	{
		$form = new Form();

		$form->addText('name', 'Jméno:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 50)
			->setRequired('Jméno je povinné');

		$form->addText('surname', 'Příjmení:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 50)
			->setRequired('Příjmení je povinné');

		$form->addInteger('birth_year', 'Rok narození:')
			->addRule(Form::LENGTH, 'Povinná délka jsou %s znaky', 4);

		$form->addText('sport', 'Hlavní sport:')
			->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 50);

		$form->addCheckbox('public', 'Zveřejnit')
			->setDefaultValue(false);

		$form->addTextArea('description', 'Popis', 100, 10)
			->setHtmlAttribute('class', 'form-wysiwyg');

		$form->addSubmit('save', 'Uložit');

		$form->onSubmit[] = function (Form $form) {
			$values = $form->getValues(true);
			$values['slug'] = Strings::webalize($values['name'] . '-' . $values['surname']);
			$member = $this->member;

			if ($member === null) {
				$member = $this->memberModel->insert($values);
				$this->flashMessage('Profil člena vytvořen');
			} else {
				$member->update($values);
				$this->flashMessage('Profil člena upraven');
			}

			$member->update([
				'slug' => $member->id . '-' . $values['slug']
			]);

			$this->redirect('this', ['id' => $member->id]);
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
			$link = WWW . '/upload/members/' . $this->member->id . '/';
			$fileName = Helper::generateFileName($fileUpload);

			if (!file_exists($link)) {
				Helper::mkdir($link);
			}

			if ($image->getHeight() > 400 || $image->getWidth() > 400) {
				$image->resize(400, 400);
			}

			$image->save($link . $fileName);
			$this->member->update(['profile' => $fileName]);
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
		unlink(WWW . '/upload/members/' . $this->member->id . '/' . $this->member->profile);
		$this->member->update(['profile' => null]);
		$this->flashMessage('Profilový obrázek byl smazán');
		$this->redirect('this');
	}

	protected function createComponentMemberGrid(): MemberGrid
	{
		return $this->memberGridFactory->create();
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

		if ($this->member->profile !== null) {
			$cropper->setImagePath('upload/members/' . $this->member->id . '/' . $this->member->profile)
				->setAspectRatio((float) 1);
		}

		$cropper->onCrop[] = function (): void {
			$this->redirect('this');
		};

		return $cropper;
	}

	protected function getMember(): ?ActiveRow
	{
		return $this->memberModel->get($this->getParameter('id'));
	}
}
