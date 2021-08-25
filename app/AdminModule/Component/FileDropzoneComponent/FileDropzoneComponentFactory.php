<?php declare(strict_types = 1);

namespace App\AdminModule\Component\FileDropzoneComponent;

use App\AdminModule\Component\FileDropzoneComponent\FileDropzoneComponent;

interface FileDropzoneComponentFactory
{

	public function create(): FileDropzoneComponent;

}
