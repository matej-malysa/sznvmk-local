<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\QueuesGrid;

interface IQueuesGridFactory
{
    /**
     * @return QueuesGrid
     */
    public function create(): QueuesGrid;
}