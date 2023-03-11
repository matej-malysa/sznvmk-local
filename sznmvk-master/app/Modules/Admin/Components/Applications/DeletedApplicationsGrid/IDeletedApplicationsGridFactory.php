<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\DeletedApplicationsGrid;

interface IDeletedApplicationsGridFactory
{
    /**
     * @return DeletedApplicationsGrid
     */
    public function create(): DeletedApplicationsGrid;
}