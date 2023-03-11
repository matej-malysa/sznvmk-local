<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\ApplicationPaymentsGrid;

interface IApplicationPaymentsGridFactory
{
    /**
     * @param int $applicationId
     * @return ApplicationPaymentsGrid
     */
    public function create(int $applicationId): ApplicationPaymentsGrid;
}