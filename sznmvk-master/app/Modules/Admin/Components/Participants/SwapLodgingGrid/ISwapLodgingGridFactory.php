<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\SwapLodgingGrid;

interface ISwapLodgingGridFactory
{
    /**
     * @return SwapLodgingGrid
     */
    public function create(int $id): SwapLodgingGrid;
}