<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\LodgingGrid;

interface ILodgingGridFactory
{
    /**
     * @return LodgingGrid
     */
    public function create(): LodgingGrid;
}