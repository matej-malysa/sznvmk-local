<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\ImportantDatesGrid;

interface IImportantDatesGridFactory
{
    /**
     * @return ImportantDatesGrid
     */
    public function create(): ImportantDatesGrid;
}