<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\ApplicationsGrid;

interface IApplicationsGridFactory
{
    public function create(): ApplicationsGrid;
}