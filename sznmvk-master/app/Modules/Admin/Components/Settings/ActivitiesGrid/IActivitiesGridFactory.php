<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\ActivitiesGrid;

interface IActivitiesGridFactory
{
    /**
     * @return ActivitiesGrid
     */
    public function create(): ActivitiesGrid;
}