<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\AddSessionForm;

interface IAddSessionFormFactory
{
    public function create(): AddSessionForm;
}