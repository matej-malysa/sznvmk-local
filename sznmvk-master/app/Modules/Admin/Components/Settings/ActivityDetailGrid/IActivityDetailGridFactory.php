<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\ActivityDetailGrid;

interface IActivityDetailGridFactory
{
    /**
     * @param int $id
     * @return ActivityDetailGrid
     */
    public function create(int $id): ActivityDetailGrid;
}