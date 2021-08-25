<?php declare(strict_types=1);

namespace App\AdminModule\Grid;

interface CustomNewGridFactory
{
	public function create(): CustomNewGrid;
}
