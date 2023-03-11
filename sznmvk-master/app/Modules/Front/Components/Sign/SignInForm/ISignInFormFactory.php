<?php

namespace App\Modules\Front\Components\Sign\SignInForm;


interface ISignInFormFactory
{
    public function create(): SignInForm;
}