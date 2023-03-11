<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\PrihlaskaRegistrationForm;

interface IPrihlaskaRegistrationFormFactory
{
    public function create(): PrihlaskaRegistrationForm;
}