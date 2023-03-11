<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\CreateApplicationForm;

interface ICreateApplicationFormFactory
{
    public function create(): CreateApplicationForm;
}