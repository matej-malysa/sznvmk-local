<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\CampDataGrid;

interface ICampDataGridFactory
{
    /**
     * @return CampDataGrid
     */
    public function create(): CampDataGrid;
}