<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\FreeLodgingGrid;

interface IFreeLodgingGridFactory
{
    /**
     * @return FreeLodgingGrid
     */
    public function create(int $id): FreeLodgingGrid;
}