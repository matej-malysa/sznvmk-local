<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\ApplicationsLogGrid;

interface IApplicationsLogGridFactory
{
    public function create(int $applicationId): ApplicationsLogGrid;
}