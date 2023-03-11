<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\EditApplicationForm;

interface IEditApplicationFormFactory
{
    public function create(int $applicationId): EditApplicationForm;
}