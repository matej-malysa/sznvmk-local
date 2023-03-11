<?php

namespace App\Components;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

interface IFormComponent
{
    public function render();

    public function formSucceeded(Form $form, ArrayHash $values);
}