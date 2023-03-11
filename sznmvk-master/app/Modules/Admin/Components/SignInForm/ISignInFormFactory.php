<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\SignInForm;

interface ISignInFormFactory
{
    public function create(): SignInForm;
}