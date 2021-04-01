<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Grid\ProductGrid;
use App\AdminModule\Grid\IProductGridFactory;
use App\Model\ProductModel;
use App\Service\Helpers;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;


class ProductPresenter extends BasePresenter
{


	/** @var ProductModel */
	private $product;

    /** @var IProductGridFactory @inject */
	public $productGridFactory;

	public function renderEdit(?int $id): void
	{

        /** @var ActiveRow $product */
        if ($id !== null && $product = $this->repository->product->get($id)) {

			$product = $product->toArray();

            /** @var Form $form */
            $form = $this['productForm'];
            $form->setDefaults($product);

            $this->template->product = $product;
        } else {
            $this->template->product = null;
        }
	}

	protected function createComponentProductForm(): Form
	{
		$form = new Form();

        $form->addHidden('id');

        $form->addText('title', 'Název')
            ->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 150)
            ->setRequired('Musíte uvést název produktu');

        $form->addUpload('image', 'Nahrát náhledový obrázek produktu');

        $form->addCheckbox('public', 'Veřejný článek')
            ->setDefaultValue(true);

        $form->addSelect('gallery_id', 'Připojit galerii')
            ->setPrompt('Žádná')
            ->setItems($this->repository->gallery->getForSelect());

        $form->addTextArea('content', 'Popis', 100, 25)
            ->setHtmlAttribute('class', 'form-wysiwyg');

        $form->addSubmit('save', 'Uložit');

        $form->onSubmit[] = function (Form $form) {
            $values = $form->getValues(true);
            $values['slug'] = Strings::webalize($values['title']);

            /** @var FileUpload $file */
            $file = $values['image'];
            unset($values['image']);

            if ($values['id'] === '') {
                unset($values['id']);
                $values['id'] = $this->repository->product->insert($values)->id;
                $this->flashMessage('Produkt přidán', 'success');
            } else {
                $this->repository->product->get($values['id'])->update($values);
                $this->flashMessage('Produkt upraven', 'success');
            }

            $link = WWW . '/upload/products/' . $values['id'] . '/';

			if (Helpers::isValidImage($file)) {
				$name = 'image.' . Helpers::getFileType($file);

				if (!file_exists($link)) {
					Helpers::mkdir($link);
				}
				$image = $file->toImage();

				if ($image->getHeight() > 1080 || $image->getWidth() > 1920) {
					$image->resize(1920, 1080);
				}

				$image->save($link . $name);
				$this->repository->product->getTable()->where('id', $values['id'])->update(['cover' => $name]);

				$this->cropper('upload/products/' . $values['id'] . '/' . $name, $this->configuration->newAspectRatio, $this->link('this', ['id' => $values['id']]));
			}

            $this->redirect('this', ['id' => $values['id']]);
        };

		return $form;
	}

    public function handleDeleteImage($newId): void
    {
        /** @var ActiveRow $image */
		$product = $this->repository->product->get($newId);
        unlink(WWW . '/upload/products/' . $product->id . '/' . $product->cover);
		$product->update(['cover' => null]);
        $this->flashMessage('Náhledový obrázek byl smazán', 'success');
        $this->redirect('this');
    }

	protected function createComponentProductGrid(): ProductGrid
	{
		return $this->productGridFactory->create();
	}
}
