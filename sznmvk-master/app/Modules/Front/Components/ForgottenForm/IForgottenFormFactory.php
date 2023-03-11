<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\ForgottenForm;

interface IForgottenFormFactory
{
    /**
     * @return ForgottenForm
     */
    public function create(): ForgottenForm;
}