<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\ZajemciGrid;

interface IZajemciGridFactory
{
    /**
     * @return ZajemciGrid
     */
    public function create(): ZajemciGrid;
}