<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\FacultiesGrid;

interface IFacultiesGridFactory
{
    /**
     * @return FacultiesGrid
     */
    public function create(): FacultiesGrid;
}