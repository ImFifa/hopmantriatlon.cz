<?php declare(strict_types = 1);

namespace App\AdminModule\Component\FileDropzoneComponent;

use Nette\Application\UI\Control;

class FileDropzoneComponent extends Control
{

	private ?string $uploadLink = null;

	private ?string $redrawLink = null;

	private ?string $prompt = null;

	public function setUploadLink(string $link): void
	{
		$this->uploadLink = $link;
	}

	public function setRedrawLink(string $link): void
	{
		$this->redrawLink = $link;
	}

	public function setPrompt(string $prompt): void
	{
		$this->prompt = $prompt;
	}

	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/FileDropzoneComponent.latte');
		$this->template->uploadLink = $this->uploadLink;
		$this->template->redrawLink = $this->redrawLink;
		$this->template->prompt = $this->prompt;
		$this->template->render();
	}

}
