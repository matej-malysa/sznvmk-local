<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\RegistrationForm;

interface IRegistrationFormFactory
{
    public function create(): RegistrationForm;
}